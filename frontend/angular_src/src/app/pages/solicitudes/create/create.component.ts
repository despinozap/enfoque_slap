import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Cliente } from 'src/app/interfaces/cliente';
import { Marca } from 'src/app/interfaces/marca';
import { ClientesService } from 'src/app/services/clientes.service';
import { MarcasService } from 'src/app/services/marcas.service';
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

  clientes: Array<Cliente> = null as any;
  marcas: Array<Marca> = null as any;
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

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
    cliente: new FormControl('', [Validators.required]),
    marca: new FormControl('', [Validators.required]),
    comentario: new FormControl('')
  });

  parteForm: FormGroup = new FormGroup({
    nparte: new FormControl('', [Validators.required]),
    cantidad: new FormControl('', [Validators.required, Validators.min(1)])
  });

  constructor(
    private router: Router,
    private _clientesService: ClientesService,
    private _marcasService: MarcasService,
    private _solicitudesService: SolicitudesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    this.solicitudForm.disable();
    this.loadClientes();
    this.loadMarcas();
  }

  private loadClientes() {
    this.loading = true;
    this._clientesService.getClientes()
      .subscribe(
        //Success request
        (response: any) => {
          this.loading = false;

          this.clientes = <Array<Cliente>>(response.data);

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
                  'Error al cargar la lista de clientes',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.clientes = null as any;
          this.loading = false;

          this.goTo_solicitudesList();
        }
      );
  }

  private loadMarcas() {
    this.loading = true;
    this._marcasService.getMarcas()
      .subscribe(
        //Success request
        (response: any) => {
          this.loading = false;

          this.marcas = <Array<Marca>>(response.data);

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
                  'Error al cargar la lista de marcas',
                  NotificationsService.messageType.error
                )

                break;
              }
          }

          this.marcas = null as any;
          this.loading = false;

          this.goTo_solicitudesList();
        }
      );
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
            this.partes.push(
              {
                "nparte": sheet[i][0],
                "cantidad": sheet[i][1]
              }
            );
          }
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

  public storeSolicitud(): void {
    this.solicitudForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let solicitud: any = {
      cliente_id: this.solicitudForm.value.cliente,
      marca_id: this.solicitudForm.value.marca,
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

            case 422: //Invalid request parameters
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
          if (this.addParte())
          {
            this.parteForm.reset();
            this.DISPLAYING_FORM = 0;
          }
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
          if (this.updateParte()) 
          {
            this.parteForm.reset();
            this.DISPLAYING_FORM = 0;
          }
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

  public addParte(): boolean {
    let parte: any = {
      "nparte": this.parteForm.value.nparte,
      "cantidad": this.parteForm.value.cantidad
    };

    this.partes.push(parte);

    return true;
  }

  public updateParte(): boolean {
    let parte: any = {
      "nparte": this.parteForm.value.nparte,
      "cantidad": this.parteForm.value.cantidad
    };

    this.partes[this.parte_index] = parte;

    return true;
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
      }
    });

  }

  public goTo_addParte(): void {
    this.PARTEFORM_STATUS = 0;
    this.DISPLAYING_FORM = 1;
  }

  public goTo_updateParte(index: number): void {

    this.parte_index = index;

    this.parteForm.controls.nparte.setValue(this.partes[this.parte_index].nparte);
    this.parteForm.controls.cantidad.setValue(this.partes[this.parte_index].cantidad);

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
