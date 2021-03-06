import { Component, OnInit, ViewChild } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { Comprador } from 'src/app/interfaces/comprador';
import { Faena } from 'src/app/interfaces/faena';
import { Marca } from 'src/app/interfaces/marca';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

/* XLSX lib */
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class SolicitudesCreateComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };

  
  dtTrigger: Subject<any> = new Subject<any>();

  loggedUser: User = null as any;
  private subLoggedUser: any;
  
  faenas: Array<Faena> = null as any;
  marcas: Array<Marca> = null as any;
  compradores: Array<Comprador> = null as any;
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;
  id: number = -1;

  sucursal_id: number = -1;

  /*
  *   Displayed form:
  * 
  *       0: Solicitud
  *       1: Parte
  */
  DISPLAYING_FORM: number = 0;

  /*
  *   FORM Parte:
  *
  *     Status:
  *       0: Agregar nueva parte
  *       1: Editar parte
  */
  PARTEFORM_STATUS: number = 0;
  private parte_index: number = -1;


  solicitudForm: FormGroup = new FormGroup({
    faena: new FormControl('', [Validators.required]),
    marca: new FormControl('', [Validators.required]),
    comprador: new FormControl('', [Validators.required]),
    comentario: new FormControl('')
  });

  parteForm: FormGroup = new FormGroup({
    nparte: new FormControl('', [Validators.required]),
    cantidad: new FormControl('', [Validators.required, Validators.min(1)])
  });

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _solicitudesService: SolicitudesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user as User;

        // Only if user is Admin or Vendedor. If it doens't, it couldn't store a new Solicitud
        if(['admin', 'seller'].includes(this.loggedUser.role.name))
        {
          // Set user's station as Sucursal
          this.sucursal_id = this.loggedUser.stationable.id;
        }
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }

    this.sub = this.route.params.subscribe(params => {
      if(params['id'] !== undefined)
      {
        // Duplicate solicitud
        this.id = params['id'];
      }
      else
      {
        // New solicitud
        this.solicitudForm.disable();
        this.prepareSolicitud();
      }
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    // If duplicating
    if(this.id >= 0)
    {
      //Prevents throwing an error for var status changed while initialization
      setTimeout(() => {
        this.loadSolicitud();
      },
      100);
    }
    
  }

  ngOnDestroy(): void {
    this.subLoggedUser.unsubscribe();
    this.sub.unsubscribe();
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

  private loadSolicitud(): void {

    this.solicitudForm.disable();
    this.loading = true;

    this.prepareSolicitud();

    this._solicitudesService.getSolicitud(this.id)
    .subscribe(
      //Success request
      (response: any) => {
      
        this.loadFormData(response.data);
        this.renderDataTable(this.datatableElement_partes);

        this.solicitudForm.enable();
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
              'Error al cargar los datos de la solicitud',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_solicitudesList();
      }
    );
  }

  private prepareSolicitud() {
    this.loading = true;
    this._solicitudesService.prepareStoreSolicitud()
      .subscribe(
        //Success request
        (response: any) => {
          this.loading = false;

          this.faenas = <Array<Faena>>(response.data.faenas);
          this.marcas = <Array<Marca>>(response.data.marcas);
          this.compradores = <Array<Comprador>>(response.data.compradores);

          this.solicitudForm.enable();
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
                  'Error al preparar la solicitud',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.faenas = null as any;
          this.loading = false;

          this.goTo_solicitudesList();
        }
      );
  }

  private loadFormData(solicitudData: any)
  {
    if(solicitudData['partes'].length > 0)
    {
      this.solicitudForm.controls.faena.setValue(solicitudData.faena.id);
      this.solicitudForm.controls.marca.setValue(solicitudData.marca.id);
      this.solicitudForm.controls.comprador.setValue(solicitudData.comprador.id);
      this.solicitudForm.controls.comentario.setValue(solicitudData.comentario);

      this.partes = [];
      solicitudData.partes.forEach((p: any) => {
        this.partes.push(
          {
            'cantidad': p.pivot.cantidad,
            'nparte': p.nparte
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
      this.goTo_solicitudesList();
    }
  }

  public onPartesFileChange(evt: any): void {
    //Catches the event
    const target: DataTransfer = <DataTransfer>(evt.target);
    // Allowed extensions. All in lowercase
    let exts: string[] = [
      "xls",
      "xlsx"
    ];

    let validationMessage = this._utilsService.validateInputFile(target, exts);

    // If valid filetype
    if (validationMessage.length === 0) {
      //Prepare reader
      const reader: FileReader = new FileReader();

      //When loading file
      reader.onload = (e: any) => {

        const bstr: string = e.target.result;

        //Read the readed file as binary sheet
        const wb: XLSX.WorkBook = XLSX.read(bstr, { type: 'binary' });
        //Get the first sheet's name
        const wsname: string = wb.SheetNames[0];
        //Get the first sheet (by name)
        const ws: XLSX.WorkSheet = wb.Sheets[wsname];

        // Dumps the whole sheet into a JSON matrix
        let sheet: any[][] = (XLSX.utils.sheet_to_json(ws, { header: 1 }));

        // Clean Partes list
        this.partes = [];
        if (sheet.length > 1) {
          for (let i = 1; i < sheet.length; i++) {
            
            if((sheet[i].length > 1) && (sheet[i][0] !== undefined) && (sheet[i][1] !== undefined) && (isNaN(sheet[i][0]) === false))
            {
              this.partes.push(
                {
                  "cantidad": sheet[i][0],
                  "nparte": sheet[i][1]
                }
              );
            }
          }
          
          this.renderDataTable(this.datatableElement_partes);
        }
        else {
          NotificationsService.showAlert(
            'El archivo cargado no contiene informacion valida',
            NotificationsService.messageType.error
          );
        }

      };

      reader.readAsBinaryString(target.files[0]);
    }
    else {
      NotificationsService.showAlert(
        validationMessage,
        NotificationsService.messageType.warning
      );
    }

  }

  public exportExcelBaseFile(): void {
    let data: any[] = [];
    //Push header
    data.push(
      [
        'Cantidad',
        'N parte'
      ]
    );

    this._utilsService.exportTableToExcel(data, `Solicitud_Nueva-Partes`);
  }

  public storeSolicitud(): void {
    this.solicitudForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let solicitud: any = {
      sucursal_id: this.sucursal_id,
      faena_id: this.solicitudForm.value.faena,
      marca_id: this.solicitudForm.value.marca,
      comprador_id: this.solicitudForm.value.comprador,
      comentario: this.solicitudForm.value.comentario,
      partes: this.partes
    };

    this._solicitudesService.storeSolicitud(solicitud)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_solicitudesList();
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
                  'Error al intentar guardar la solicitud',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.solicitudForm.enable();
          this.loading = false;
        }
      );
  }

  public submitFormParte(): void {

    let index = this.partes.findIndex((p) => {
      if((this.parteForm.controls.nparte.value.length > 0) && (p['nparte'].toUpperCase() === this.parteForm.controls.nparte.value.toUpperCase()))
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    switch (this.PARTEFORM_STATUS) 
    {
      case 0: //Add a new Parte
      {
        // If doesn't exist in list
        if(index < 0)
        {
          this.addParte();
          this.parteForm.reset();
          this.DISPLAYING_FORM = 0;
        }
        else
        {
          NotificationsService.showAlert(
            'La lista ya contiene una parte con el numero de parte ingresado',
            NotificationsService.messageType.warning
          );
        }
        
        break;
      }

      case 1: //Update an existing Parte 
      {
        // If doesn't exist in list or it's the same item
        if((index < 0) || (index === this.parte_index))
        {
          this.updateParte();
          this.parteForm.reset();
          this.DISPLAYING_FORM = 0;
        }
        else
        {
          NotificationsService.showAlert(
            'La lista ya contiene una parte con el numero de parte editado',
            NotificationsService.messageType.warning
          );
        }

        break;
      }

      default: 
      {

        break;
      }
    }

  }

  public addParte(): void {
    let parte: any = {
      "cantidad": this.parteForm.value.cantidad,
      "nparte": this.parteForm.value.nparte
    };

    this.partes.push(parte);
    this.renderDataTable(this.datatableElement_partes);
  }

  public updateParte(): void {
    let parte: any = {
      "cantidad": this.parteForm.value.cantidad,
      "nparte": this.parteForm.value.nparte
    };

    this.partes[this.parte_index] = parte;
    this.renderDataTable(this.datatableElement_partes);
  }

  public removeParte(index: number): void
  {
    Swal.fire({
      title: 'Eliminar parte',
      text: "¿Realmente deseas eliminar la parte?",
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
        this.partes.splice(index, 1);
        this.renderDataTable(this.datatableElement_partes);
      }
    });

  }

  public goTo_addParte(): void {
    this.PARTEFORM_STATUS = 0;
    this.DISPLAYING_FORM = 1;
  }

  public goTo_updateParte(index: number): void {

    this.parte_index = index;

    this.parteForm.controls.cantidad.setValue(this.partes[this.parte_index].cantidad);
    this.parteForm.controls.nparte.setValue(this.partes[this.parte_index].nparte);

    this.PARTEFORM_STATUS = 1;
    this.DISPLAYING_FORM = 1;
  }

  public goTo_newSolicitud(): void {
    this.parteForm.reset();
    this.DISPLAYING_FORM = 0;
  }

  public goTo_solicitudesList(): void {
    this.router.navigate(['/panel/solicitudes']);
  }

}
