import { AfterViewInit, Component, OnInit, QueryList, ViewChild, ViewChildren } from '@angular/core';
import { Location } from '@angular/common';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { AuthService } from 'src/app/services/auth.service';
import { CotizacionesService } from 'src/app/services/cotizaciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';
import { PDFCotizacionComponent } from '../../pdfs/cotizacion/cotizacion.component';
import { User } from 'src/app/interfaces/user';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class CotizacionesDetailsComponent implements OnInit, AfterViewInit {

  @ViewChild('reportCotizacion') reportCotizacion: PDFCotizacionComponent = null as any;
  @ViewChildren(DataTableDirective)
  datatableELements: QueryList<DataTableDirective> = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };
  dtOptionsAprobar: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    columnDefs: [
      { 
        targets: [0, 1, 5],
        orderable: false
      }
    ],
    order: [[1, 'desc']]
  };
  
  dtTrigger: Subject<any> = new Subject<any>();
  dtTriggerAprobar: Subject<any> = new Subject<any>();

  loggedUser: User = null as any;
  private subLoggedUser: any;

  cotizacion: any = {
    // Common fields
    id: -1,
    created_at: null,
    faena_name: null,
    usdvalue: null,
    monto: null,
    // Details
    dias: -1,
    cliente_name: null,
    marca_name: null,
    comprador_name: null,
    estadocotizacion_id: -1,
    estadocotizacion_name: null,
    motivorechazo_name: null,
    // Report
    solicitud_id: -1,
    sucursal_rut: null,
    sucursal_name: null,
    sucursal_address: null,
    sucursal_city: null,
    faena_rut: null,
    faena_address: null,
    faena_city: null,
    faena_contact: null,
    faena_phone: null,
    user_name: null,
    user_email: null,
    user_phone: null
  };

  motivosRechazo: Array<any> = null as any;
  partes: any[] = [];
  partesAprobadas: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  estadoComercialForm: FormGroup = new FormGroup({
    decision: new FormControl('')
  });

  estadoComercialAprobarForm: FormGroup = new FormGroup({
    noccliente: new FormControl('', [Validators.required, Validators.minLength(1)])
  });
  dococcliente: File = null as any;

  estadoComercialRechazarForm: FormGroup = new FormGroup({
    motivorechazo_id: new FormControl('', [Validators.required]),
  });

  private sub: any;

  /*
  *   Displayed form:
  * 
  *       0: Partes list
  *       1: Estado comercial
  */
  DISPLAYING_FORM: number = 0;

  /*
  *   Estado comercial form:
  * 
  *       0: Aprobar
  *       1: Rechazar
  */
  ESTADOCOMERCIAL_FORM: number = -1;
  
  constructor(
    private location: Location,
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _cotizacionesService: CotizacionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user as User;
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }

    this.sub = this.route.params.subscribe(params => {
      this.cotizacion.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();
    this.dtTriggerAprobar.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadCotizacion();
    },
    100);
  }

  ngOnDestroy(): void {
    this.sub.unsubscribe();
    this.subLoggedUser.unsubscribe();
    this.dtTrigger.unsubscribe();
    this.dtTriggerAprobar.unsubscribe();
  }
  
  private renderDataTable(dataTableElement: DataTableDirective, trigger: Subject<any>): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the trigger to rerender again
      trigger.next();
    });
  }
  
  private loadFormData(cotizacionData: any)
  { 
    if(cotizacionData['partes'].length > 0)
    {      
      // Common fields
      this.cotizacion.id = cotizacionData.id;
      this.cotizacion.created_at = cotizacionData.created_at;
      
      this.cotizacion.faena_name = cotizacionData.solicitud.faena.name;
      this.cotizacion.usdvalue = cotizacionData.usdvalue;

      // Details
      this.cotizacion.dias = cotizacionData.dias;
      this.cotizacion.monto = cotizacionData.monto;
      this.cotizacion.cliente_name = cotizacionData.solicitud.faena.cliente.name;
      this.cotizacion.marca_name = cotizacionData.solicitud.marca.name;
      this.cotizacion.comprador_name = cotizacionData.solicitud.comprador.name;
      this.cotizacion.estadocotizacion_id = cotizacionData.estadocotizacion.id,
      this.cotizacion.estadocotizacion_name = cotizacionData.estadocotizacion.name;
      // Details - If Rechazada, then store Motivo rechazo name
      this.cotizacion.motivorechazo_name = ((this.cotizacion.estadocotizacion_id === 4) && (cotizacionData.motivorechazo !== null)) ? cotizacionData.motivorechazo.name : null

      // Report
      let today = new Date();
      this.cotizacion.currentdate = `${today.getFullYear()}-${(today.getMonth() + 1) < 10 ? '0' + (today.getMonth() + 1) : (today.getMonth() + 1)}-${today.getDate() < 10 ? '0' + today.getDate() : today.getDate()}`;
      this.cotizacion.solicitud_id = cotizacionData.solicitud.id;
      this.cotizacion.sucursal_rut = cotizacionData.solicitud.sucursal.rut;
      this.cotizacion.sucursal_name = cotizacionData.solicitud.sucursal.name;
      this.cotizacion.sucursal_address = cotizacionData.solicitud.sucursal.address;
      this.cotizacion.sucursal_city = cotizacionData.solicitud.sucursal.city;
      this.cotizacion.faena_rut = cotizacionData.solicitud.faena.rut;
      this.cotizacion.faena_address = cotizacionData.solicitud.faena.address;
      this.cotizacion.faena_city = cotizacionData.solicitud.faena.city;
      this.cotizacion.faena_contact = cotizacionData.solicitud.faena.contact;
      this.cotizacion.faena_phone = cotizacionData.solicitud.faena.phone;
      this.cotizacion.user_name = cotizacionData.solicitud.user.name;
      this.cotizacion.user_email = cotizacionData.solicitud.user.email;
      this.cotizacion.user_phone = cotizacionData.solicitud.user.phone;

      this.partes = [];
      cotizacionData.partes.forEach((p: any) => {
        this.partes.push(
          {
            'id': p.id,
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            //'costo': p.pivot.costo,
            //'margen': p.pivot.margen,
            'tiempoentrega': p.pivot.tiempoentrega,
            //'peso': p.pivot.peso,
            //'flete': p.pivot.flete,
            'monto': p.pivot.monto,
            'backorder': p.pivot.backorder === 1 ? true : false,
          }
        )
      });
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_back();
    }
  }

  public loadCotizacion(): void {
    
    this.loading = true;
    let data = {
      cotizaciones: [this.cotizacion.id]
    };

    this._cotizacionesService.getReportCotizacion(data)
    .subscribe(
      //Success request
      (response: any) => {
        // Loads the first item
        if(response.data.length > 0)
        {
          this.loadFormData(response.data[0]);
          // Uses the first datatables instance
          this.renderDataTable(this.datatableELements.first, this.dtTrigger);

          this.loading = false;
        }
        else
        {
          NotificationsService.showToast(
            'Error al cargar los datos de la cotizacion',
            NotificationsService.messageType.error
          );

          this.loading = false;
          this.goTo_back();
        }
        
      },
      //Error request
      (errorResponse: any) => {
        
        switch(errorResponse.status)
        {
        
          case 400: //Bad request
          {
            NotificationsService.showToast(
              'Error al cargar los datos de la cotizacion',
              NotificationsService.messageType.error
            );

            break;
          }

          case 405: //Permission denied
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 500: //Internal server
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            NotificationsService.showToast(
              'Error al cargar los datos de la cotizacion',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_back();
      }
    );
  }

  public generateReportCotizacionPDF(): void {  

    // Go to top of page for report rendering
    window.scroll(0,0);

    // If report component was found
    if(this.reportCotizacion !== undefined)
    {
      let partesClp = this.partes.map((parte: any) => 
        {
          let parteClp = {...parte}; // Clone the current part
          parteClp.monto = parteClp.monto * this.cotizacion.usdvalue; // Assign new monto in CLP
          
          return parteClp;
        }
      );

      let reportData = {
        cotizacion: this.cotizacion,
        partes: partesClp
      };

      // Set report data
      this.reportCotizacion.reportData = reportData;

      // Export report to PDF after 1 sec
      setTimeout(() => {
          this.reportCotizacion.exportCotizacionToPdf();
        },
        1000
      );
      
    }
    else
    {
      NotificationsService.showToast(
        'Error al generar el reporte de cotizacion',
        NotificationsService.messageType.error
      );
    }
  }

  private loadMotivosRechazo() {
    this.estadoComercialRechazarForm.disable();
    this.loading = true;

    this._cotizacionesService.getMotivosRechazoFull()
      .subscribe(
        //Success request
        (response: any) => {
          this.loading = false;

          this.motivosRechazo = <Array<any>>(response.data);

          this.estadoComercialRechazarForm.enable();
        },
        //Error request
        (errorResponse: any) => {

          switch (errorResponse.status) 
          {
            case 405: //Permission denied
            {
              NotificationsService.showToast(
                errorResponse.error.message,
                NotificationsService.messageType.error
              );

              break;
            }

            case 500: //Internal server
              {
                NotificationsService.showToast(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            default: //Unhandled error
              {
                NotificationsService.showToast(
                  'Error al cargar la lista de motivos de rechazo',
                  NotificationsService.messageType.error
                )

                break;
              }
          }

          this.motivosRechazo = null as any;
          this.loading = false;

          this.goTo_partesList();
        }
      );
  }

  // Modified for removing decimals when in CLP value
  public moneyStringFormat(value: number): string {
    let moneyStr = this._utilsService.moneyStringFormat(value);
    
    // If role is Seller
    if((this.loggedUser !== null) && (this.loggedUser.role.name === 'seller'))
    {
      // Modify value removing decimals
      let index = moneyStr.indexOf('.');
      moneyStr = moneyStr.substring(0, index);
    }
    
    return moneyStr;
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public estadoComercial_decisionChanged(): void {
    let value = this.estadoComercialForm.controls.decision.value;

    if(value !== '')
    {
      switch(parseInt(value))
      {
        case 0:
          {
            this.goTo_estadoComercial_aprobar();
            
            break;
          }

        case 1:
          {
            this.goTo_estadoComercial_rechazar();

            break;
          }

        default: 
        {

          break;
        }
      }
    }
    else
    {
      this.ESTADOCOMERCIAL_FORM = -1;
    }
    
  }

  public onFileDocOCClienteSelected(evt: any): void {
    if(evt.target.files.length > 0)
    {
      this.dococcliente = <File>evt.target.files[0];
    }
    else
    {
      this.dococcliente = null as any;
    }
  }

  public preSubmitFormEstadoComercial_aprobar(): void {
    if(this.dococcliente === null)
    {
      Swal.fire({
        title: 'Aprobar cotizacion',
        text: "No has ingresado el archivo OC cliente. ¿Deseas aprobar la cotizacion sin el documento?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#555555',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false
      }).then((result: any) => {
        if(result.isConfirmed)
        {
          this.submitFormEstadoComercial_aprobar();
        }
      });
    }
    else
    {
      this.submitFormEstadoComercial_aprobar();
    }
  }

  public submitFormEstadoComercial_aprobar(): void {
    this.loading = true;
    this.responseErrors = [];

    this.estadoComercialForm.disable();
    this.estadoComercialAprobarForm.disable();

    let partes: any[] = [];
    //Add checked partes
    this.partesAprobadas.forEach((parte: any) => {

      // Adding only required data to send
      let dataParte: any = {
        id: parte.id,
        cantidad: parte.cantidad,
      };

      partes.push(dataParte);
    });

    const data = new FormData();
    data.append('partes', JSON.stringify(partes));
    data.append('noccliente', this.estadoComercialAprobarForm.value.noccliente);
    //Defining the file as an empty string when null it's interpreted as null by server
    data.append('dococcliente', this.dococcliente !== null ? this.dococcliente : '');

    this._cotizacionesService.approveCotizacion(this.cotizacion.id, data)
      .subscribe(
        //Success request
        (response: any) => {
          
          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );
          
          this.goTo_back();
        },
        //Error request
        (errorResponse: any) => {
          switch (errorResponse.status) 
          {
            case 400: //Invalid request parameters
              {
                this.responseErrors = errorResponse.error.message;

                break;
              }

            case 405: //Permission denied
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 409: //Conflict
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 412: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 500: //Internal server
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            default: //Unhandled error
              {
                NotificationsService.showAlert(
                  'Error al intentar aprobar la cotizacion',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.estadoComercialForm.enable();
          this.estadoComercialAprobarForm.enable();
          this.loading = false;
        }
      );
  }

  public submitFormEstadoComercial_rechazar(): void {
    this.loading = true;
    this.responseErrors = [];

    this.estadoComercialForm.disable();
    this.estadoComercialRechazarForm.disable();

    let data: any = {
      motivorechazo_id: this.estadoComercialRechazarForm.value.motivorechazo_id
    };
    
    this._cotizacionesService.rejectCotizacion(this.cotizacion.id, data)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_back();
        },
        //Error request
        (errorResponse: any) => {
          switch (errorResponse.status) 
          {
            case 400: //Invalid request parameters
              {
                this.responseErrors = errorResponse.error.message;

                break;
              }

            case 405: //Permission denied
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }
            
            case 409: //Conflict
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 412: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            case 500: //Internal server
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

                break;
              }

            default: //Unhandled error
              {
                NotificationsService.showAlert(
                  'Error al intentar rechazar la cotizacion',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.estadoComercialForm.enable();
          this.estadoComercialRechazarForm.enable();
          this.loading = false;
        }
      );
  }

  public goTo_estadoComercial(): void {
    this.estadoComercialForm.controls.decision.setValue(-1);

    this.ESTADOCOMERCIAL_FORM = -1;
    this.DISPLAYING_FORM = 1;
  }

  public checkPartesList(evt: any): void {
    this.partesAprobadas.forEach((parte: any) => {
      parte.checked = evt.target.checked;
    });
  }

  public checkParteItem(parte: any, evt: any): void {
    parte.checked = evt.target.checked;

    this.sortPartesByChecked();
  }

  public isCheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === true)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }

  public isUncheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === false)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }

  // Modified for sorting partesAprobadas
  private sortPartesByChecked(): void {
    // Sort partes pushing checked ones to the top
    this.partesAprobadas = this.partesAprobadas.sort((p1, p2) => {
      return ((p2.checked === true) ? 1 : 0) - ((p1.checked === true) ? 1 : 0);
    });
  }

  public updateParteAprobada_cantidad(parteAprobada: any, evt: any): void {
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0))
    {
      parteAprobada.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parteAprobada.cantidad;
    }
  }

  public goTo_estadoComercial_aprobar(): void {
    
    // Copy partesAprobadas from partes list
    this.partesAprobadas = this.partes.map((parte) => {
        // Returns cloned parte, different object
        return {...parte};
      }
    );

    // Uses the second (and last) datatables instance
    this.renderDataTable(this.datatableELements.last, this.dtTriggerAprobar);
    //Simulates the Event (evt) for checking all items in list using the same function
    let evt = {
      "target": {
        "checked" : true
      }
    } as any;
    // Check all items in list
    this.checkPartesList(evt);

    this.estadoComercialAprobarForm.reset();
    this.ESTADOCOMERCIAL_FORM = 0;
  }

  public goTo_estadoComercial_rechazar(): void {
    this.motivosRechazo = null as any;
    this.loadMotivosRechazo();

    this.ESTADOCOMERCIAL_FORM = 1;
  }

  public goTo_partesList(): void {
    this.ESTADOCOMERCIAL_FORM = -1;
    this.DISPLAYING_FORM = 0;
  }

  public goTo_back(): void {
    this.location.back();
  }

}
