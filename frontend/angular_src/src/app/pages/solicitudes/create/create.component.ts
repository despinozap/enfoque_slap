import { Component, OnInit, ɵSWITCH_COMPILE_INJECTABLE__POST_R3__ } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Cliente } from 'src/app/interfaces/cliente';
import { Marca } from 'src/app/interfaces/marca';
import { ClientesService } from 'src/app/services/clientes.service';
import { MarcasService } from 'src/app/services/marcas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { UtilsService } from 'src/app/services/utils.service';

//XLSX lib
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
  * Status:
  *   0: Solicitud
  *   1: Agregar parte manual
  */
  SOLICITUDFORM_STATUS: number = 0;


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

  private loadClientes()
  {
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

        switch(errorResponse.status)
        {     
          case 500: //Internal server
          {
            NotificationsService.showAlert(
              errorResponse.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            NotificationsService.showAlert(
              'Error al cargar la lista de clientes',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }
        
        this.clientes = null as any;
        this.loading = false;

        this.goTo_solicitudesList();
      }
    );  
  }

  private loadMarcas()
  {
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

        switch(errorResponse.status)
        {     
          case 500: //Internal server
          {
            NotificationsService.showAlert(
              errorResponse.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            NotificationsService.showAlert(
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

  public onPartesFileChange(evt: any): void 
  {
    //Catches the event
    const target: DataTransfer = <DataTransfer>(evt.target);
    // Allowed extensions. All in lowercase
    let exts: string[] = [
      "xls",
      "xlsx"
    ];

    let validationMessage = this._utilsService.validateInputFile(target, exts);

    // If valid filetype
    if(validationMessage.length === 0)
    {
      //Prepare reader
      const reader: FileReader = new FileReader();

      //When loading file
      reader.onload = (e: any) => {

        const bstr: string = e.target.result;
        
        //Read the readed file as binary sheet
        const wb: XLSX.WorkBook = XLSX.read(bstr, {type: 'binary'});
        //Get the first sheet's name
        const wsname: string = wb.SheetNames[0];
        //Get the first sheet (by name)
        const ws: XLSX.WorkSheet = wb.Sheets[wsname];

        // Dumps the whole sheet into a JSON matrix
        let sheet: any[][] = (XLSX.utils.sheet_to_json(ws, {header: 1}));

        // Clean Partes list
        this.partes = [];

        if(sheet.length > 1)
        {
          for(let i = 1; i < sheet.length; i++)
          {
            this.partes.push(
              {
                "nparte": sheet[i][0],
                "cantidad": sheet[i][1]
              }
            );
          }
        }
        else
        {
          NotificationsService.showAlert(
            'El archivo cargado no contiene informacion valida',
            NotificationsService.messageType.error
          );
        }

      };

      reader.readAsBinaryString(target.files[0]);
    }
    else
    {
      NotificationsService.showAlert(
        validationMessage,
        NotificationsService.messageType.warning
      );
    }
    
  }

  public storeSolicitud():void {
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

        switch(errorResponse.status)
        {
          case 400: //Invalid request parameters
          {
            this.responseErrors = errorResponse.error.message;

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

  public removeParte(nparte: string)
  {
    this.partes = this.partes.filter((parte) => {
      if(parte["nparte"] !== nparte)
      {
        return parte;
      }
    });
  }

  public addParte(): void
  {
    let parte: any = {
      "nparte": this.parteForm.value.nparte,
      "cantidad": this.parteForm.value.cantidad
    };

    this.partes.push(parte);

    this.parteForm.reset();
    this.SOLICITUDFORM_STATUS = 0;
  }

  public goTo_addParte(): void {
    this.SOLICITUDFORM_STATUS = 1;
  }

  public goTo_newSolicitud(): void
  {
    this.parteForm.reset();
    this.SOLICITUDFORM_STATUS = 0;
  }

  public goTo_solicitudesList()
  {
    this.router.navigate(['/panel/solicitudes']);
  }

}
