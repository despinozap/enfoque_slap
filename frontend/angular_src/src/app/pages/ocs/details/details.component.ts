import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { AbstractControl, FormControl, FormGroup, ValidatorFn, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { OcsService } from 'src/app/services/ocs.service';
import { ProveedoresService } from 'src/app/services/proveedores.service';
import { UtilsService } from 'src/app/services/utils.service';
import { AuthService } from 'src/app/services/auth.service';
import { User } from 'src/app/interfaces/user';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');


@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class OcsDetailsComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };

  dtTrigger: Subject<any> = new Subject<any>();
  
  loggedUser: User = null as any;
  private subLoggedUser: any;

  oc: any = {
    id: -1,
    created_at: null,
    dias: -1,
    faena_name: null,
    cliente_name: null,
    marca_name: null,
    comprador_name: null,
    proveedor_name: null,
    comprador_id: null,
    occliente_url: null,
    occliente_filename: null,
    estadooc_id: -1,
    estadooc_name: null,
    solicitud_id: -1,
    cotizacion_id: -1
  };

  public parte_index: number = -1;
  public parte_min: number = -1;
  parteTrack: any = null;

  motivosBaja: Array<any> = null as any;
  partes: any[] = [];
  proveedores: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  /*
  *   Displayed form:
  * 
  *       0: Partes list
  *       1: Parte edit
  *       2: Reject (dar baja)
  *       3: Start OC
  */
  DISPLAYING_FORM: number = 0;

  parteForm: FormGroup = new FormGroup({
    cantidad: new FormControl('', [Validators.required, Validators.min(1)]),
    tiempoentrega: new FormControl('', [Validators.required, Validators.min(0)]),
    backorder: new FormControl(''),
  });

  darBajaOCForm: FormGroup = new FormGroup({
    motivobaja_id: new FormControl('', [Validators.required]),
  });

  startOCForm: FormGroup = new FormGroup({
    proveedor_id: new FormControl('', [Validators.required]),
  });

  constructor(
    private location: Location,
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _ocsService: OcsService,
    private _proveedoresService: ProveedoresService,
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
      this.oc.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadOC();
    },
    100);
  }

  ngOnDestroy(): void {
    this.sub.unsubscribe();
    this.subLoggedUser.unsubscribe();
    this.dtTrigger.unsubscribe();
  }

  private renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }
  
  private loadFormData(ocData: any)
  { 
    if(ocData['partes'].length > 0)
    {
      this.oc.id = ocData.id;
      this.oc.created_at = ocData.created_at;
      this.oc.dias = ocData.dias;
      this.oc.faena_name = ocData.cotizacion.solicitud.faena.name;
      this.oc.cliente_name = ocData.cotizacion.solicitud.faena.cliente.name;
      this.oc.marca_name = ocData.cotizacion.solicitud.marca.name;
      this.oc.comprador_name = ocData.cotizacion.solicitud.comprador.name;
      if(ocData.proveedor !== null)
      {
        this.oc.proveedor_name = ocData.proveedor.name;
      }
      else
      {
        this.oc.proveedor_name = '[No asignado]';
      }
      
      if(ocData.filedata !== null)
      {
        this.oc.occliente_url = ocData.filedata.url;
        this.oc.occliente_filename = ocData.filedata.filename;
      }
      this.oc.comprador_id = ocData.cotizacion.solicitud.comprador.id;
      this.oc.estadooc_id = ocData.estadooc.id;
      this.oc.estadooc_name = ocData.estadooc.name;
      this.oc.solicitud_id = ocData.cotizacion.solicitud.id;
      this.oc.cotizacion_id = ocData.cotizacion.id;

      this.partes = [];
      let statusDays = null
      ocData.partes.forEach((p: any) => {
        
        // Gets time diff from last pivot update to today (in ms) and convert it to days
        statusDays = Math.floor(((new Date().getTime()) - (new Date(p.pivot.updated_at).getTime())) / (1000 * 60 * 60 * 24));
        this.partes.push(
          {
            'id': p.id,
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            'cantidad_entregado': p.pivot.cantidad_entregado,
            'cantidad_stock': p.pivot.cantidad_stock,
            'tiempoentrega': p.pivot.tiempoentrega,
            'backorder': p.pivot.backorder === 1 ? true : false,
            'updated_at': p.pivot.updated_at,
            'statusdays': statusDays,
            'estadoocparte_id': p.pivot.estadoocparte.id,
            'estadoocparte_name': p.pivot.estadoocparte.name,
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

  public loadOC(): void {
    
    this.loading = true;

    let data = {
      ocs: [this.oc.id]
    };

    this._ocsService.getReportOc(data)
    .subscribe(
      //Success request
      (response: any) => {

        // Loads the first item
        if(response.data.length > 0)
        {
          this.loadFormData(response.data[0]);
          this.renderDataTable(this.datatableElement_partes);

          this.loading = false;
        }
        else
        {
          NotificationsService.showToast(
            'Error al cargar los datos de la OC',
            NotificationsService.messageType.error
          );

          this.loading = false;
          this.goTo_back();
        }

      },
      //Error request
      (errorResponse: any) => {
        console.log(errorResponse);
        switch(errorResponse.status)
        {
        
          case 405: //Permission denied
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
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
              'Error al cargar los datos de la OC',
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

  private loadMotivosBaja() {
    this.darBajaOCForm.disable();
    this.loading = true;

    this._ocsService.getMotivosBajaFull()
      .subscribe(
        //Success request
        (response: any) => {
          this.loading = false;

          this.motivosBaja= <Array<any>>(response.data);

          this.darBajaOCForm.enable();
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
                  'Error al cargar la lista de motivos de baja',
                  NotificationsService.messageType.error
                )

                break;
              }
          }

          this.motivosBaja = null as any;
          this.loading = false;

          this.goTo_partesList();
        }
      );
  }

  public submitFormDarBajaOC(): void {
    this.loading = true;
    this.responseErrors = [];

    this.darBajaOCForm.disable();

    let data: any = {
      motivobaja_id: this.darBajaOCForm.value.motivobaja_id
    };
    
    this._ocsService.rejectOC(this.oc.id, data)
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
                  'Error al intentar dar de baja la OC',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.darBajaOCForm.enable();
          this.loading = false;
        }
      );
  }

  public startOC(): void{
    Swal.fire({
      title: 'Activar OC',
      text: `¿Realmente quieres activar proceso compra de la OC #${ this.oc.id }?`,
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
        this.goTo_startOC();
      }
    });
  }

  public loadProveedores(): void {
    
    this.loading = true;

    this._proveedoresService.getProveedores(this.oc.comprador_id)
    .subscribe(
      //Success request
      (response: any) => {

        this.proveedores = response.data;
        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {
        
          case 405: //Permission denied
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
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
              'Error al cargar los datos de los proveedores',
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

  public submitFormStartOC(): void {
    this.loading = true;
    this.responseErrors = [];

    this.startOCForm.disable();

    let data: any = {
      proveedor_id: this.startOCForm.value.proveedor_id
    };
    
    this._ocsService.startOC(this.oc.id, data)
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
                  'Error al intentar activar el proceso de compra de la OC',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.startOCForm.enable();
          this.loading = false;
        }
      );
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public goTo_darBaja(): void {
    this.motivosBaja = null as any;
    this.loadMotivosBaja();

    this.responseErrors = [];
    this.DISPLAYING_FORM = 2;
  }

  public goTo_startOC(): void {
    this.proveedores = null as any;
    this.loadProveedores();

    this.responseErrors = [];
    this.DISPLAYING_FORM = 3;
  }

  public updateParte(): void {
    this.parteForm.disable();
    this.loading = true;
    this.responseErrors = [];
    
    let parte: any = {
      nparte: this.partes[this.parte_index].nparte,
      cantidad: this.parteForm.value.cantidad,
      tiempoentrega: this.parteForm.value.tiempoentrega,
      backorder: this.parteForm.value.backorder
    };
    
    this._ocsService.updateParte(this.oc.id, parte)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
          );
          
          this.partes[this.parte_index].cantidad = this.parteForm.value.cantidad;
          this.partes[this.parte_index].tiempoentrega = this.parteForm.value.tiempoentrega;
          this.partes[this.parte_index].backorder = this.parteForm.value.backorder;
          this.parteForm.reset();
          this.parteForm.enable();
          this.loading = false;
          
          this.renderDataTable(this.datatableElement_partes);
          this.goTo_partesList();
        },
        //Error request
      (errorResponse: any) => {
        switch(errorResponse.status)
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
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_partesList();

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
              'Error al actualizar la parte',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.parteForm.enable();
        this.loading = false;
      }
    );

  }

  public removeParte(parte: any)
  {
    Swal.fire({
      title: 'Eliminar parte',
      text: `¿Realmente quieres eliminar la parte "${ parte.descripcion }" de la OC?`,
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
        Swal.queue([{
          title: 'Eliminando..',
          icon: 'warning',
          showConfirmButton: false,
          showCancelButton: false,
          allowOutsideClick: false,
          showLoaderOnConfirm: true,
          preConfirm: () => {

          }    
        }]);

        this._ocsService.removeParte(this.oc.id, parte.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadOC();

            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

          },
          //Error request
          (errorResponse: any) => {
            console.log(errorResponse);
            switch(errorResponse.status)
            {
              case 400: //Bad request
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

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
                  NotificationsService.messageType.warning
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
                  'Error al intentar eliminar la parte',
                  NotificationsService.messageType.error
                );

                break;
              }
            }
          }
        );
      }
    });

  } 

  public goTo_updateParte(index: number): void {

    this.parte_index = index;

    this.parteForm.controls.cantidad.setValue(this.partes[this.parte_index].cantidad);
    this.parteForm.controls.tiempoentrega.setValue(this.partes[this.parte_index].tiempoentrega);
    this.parteForm.controls.backorder.setValue(this.partes[this.parte_index].backorder);

    this.responseErrors = [];
    this.DISPLAYING_FORM = 1;
  }

  public goTo_partesList(): void {
    this.DISPLAYING_FORM = 0;
  }

  public goTo_back(): void {
    this.location.back();
  }

}
