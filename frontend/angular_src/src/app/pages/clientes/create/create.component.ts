import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Cliente } from 'src/app/interfaces/cliente';
import { ClientesService } from 'src/app/services/clientes.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})

export class ClientesCreateComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];

  clienteForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
  });

  constructor(
    private _clientesService: ClientesService,
    private router: Router
  ) {
  }

  ngOnInit(): void {
  }

  public storeCliente():void {
    this.clienteForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let cliente: any = {
      name: this.clienteForm.value.name,
      country_id: 1 // Chile
    } as Cliente;

    this._clientesService.storeCliente(cliente)
    .subscribe(
      //Success request
      (response: any) => {

        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_clientesList();
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
              'Error al intentar guardar el cliente',
              NotificationsService.messageType.error
            );

            break;
          }
        }

        this.clienteForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_clientesList()
  {
    this.router.navigate(['/panel/clientes']);
  }

}
