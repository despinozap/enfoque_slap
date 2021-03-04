import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Cliente } from 'src/app/interfaces/cliente';
import { Solicitud } from 'src/app/interfaces/solicitud';
import { ClientesService } from 'src/app/services/clientes.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class SolicitudesCreateComponent implements OnInit {

  public clientes: Array<Cliente> = null as any;
  public partes: any[] = null as any;
  loading: boolean = false;
  responseErrors: any = [];

  solicitudForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.min(0)]),
    role: new FormControl('', [Validators.required])
  });

  constructor(
    private _clientesService: ClientesService,
    private _solicitudesService: SolicitudesService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.solicitudForm.disable();
    this.loadClientes();
    this.storeSolicitud();
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
        console.log(this.clientes);
        
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

  public storeSolicitud():void {
    this.solicitudForm.disable();
    this.loading = true;
    this.responseErrors = [];

    /*
    * REMOVE THIS BLOCK
    */
    {
      this.partes = [
        {
          "id": 1,
          "cantidad": 1450
        }
      ];
    }

    let solicitud: Solicitud = {
      cliente_id: 1,
      user_id: 1,
      estadosolicitud_id: 1,
      comentario: 'sads',
      partes: this.partes
    } as Solicitud;

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
            this.responseErrors = errorResponse.error.message;

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

  public goTo_solicitudesList()
  {
    this.router.navigate(['/panel/solicitudes']);
  }

}
