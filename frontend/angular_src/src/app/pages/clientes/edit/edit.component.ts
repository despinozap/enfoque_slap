import { Component, OnInit } from '@angular/core';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Cliente } from 'src/app/interfaces/cliente';
import { ClientesService } from 'src/app/services/clientes.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class ClientesEditComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];
  
  private sub: any;
  private id: number = -1;

  clienteForm: FormGroup = new FormGroup({
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
  });
  

  constructor(
    private route: ActivatedRoute, 
    private _clientesService: ClientesService,
    private router: Router
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      
      this.id = params['id'];
      this.clienteForm.disable();
      this.loading = true;

      this._clientesService.getCliente(this.id)
      .subscribe(
        //Success request
        (response: any) => {
          this.clienteForm.enable();
          this.loading = false;
          
          this.loadFormData(response.data);
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
          
            default: //Unhandled error
            {
              NotificationsService.showToast(
                'Error al cargar los datos del cliente',
                NotificationsService.messageType.error
              );
    
              break;

            }
          }

          this.loading = false;
          this.goTo_clientesList();
        }
      );
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(clienteData: any)
  {
    this.clienteForm.controls.name.setValue(clienteData.name);
  }

  public updateCliente()
  {
    this.clienteForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let cliente: Cliente = {
      name: this.clienteForm.value.name,
    } as Cliente;
    
    this._clientesService.updateCliente(this.id, cliente)
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
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_clientesList();

            break;
          }
        
          case 422: //Invalid request parameters
          {
            this.responseErrors = errorResponse.error;

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
              'Error al actualizar el cliente',
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
