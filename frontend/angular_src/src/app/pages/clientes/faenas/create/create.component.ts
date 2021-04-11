import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Faena } from 'src/app/interfaces/faena';
import { ClientesService } from 'src/app/services/clientes.service';
import { FaenasService } from 'src/app/services/faenas.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class FaenasCreateComponent implements OnInit {

  cliente: any = {
    id: null,
    name: null,
  };

  faenas: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  faenaForm: FormGroup = new FormGroup({
    rut: new FormControl('', [Validators.required, Validators.minLength(1)]),
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    address: new FormControl('', [Validators.required, Validators.minLength(1)]),
    city: new FormControl('', [Validators.required, Validators.minLength(1)]),
    contact: new FormControl('', [Validators.required, Validators.minLength(1)]),
    phone: new FormControl('', [Validators.required, Validators.minLength(1)]),
  });

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _clientesService: ClientesService,
    private _faenasService: FaenasService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.cliente.id = params['cliente_id'];
      this.loadCliente();
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(clienteData: any)
  {
    this.cliente.name = clienteData.name;
    this.faenas = clienteData.faenas;
  }

  public loadCliente(): void {
    
    this.loading = true;

    this._clientesService.getCliente(this.cliente.id)
    .subscribe(
      //Success request
      (response: any) => {
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
              'Error al cargar los datos del cliente',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_faenasList();
      }
    );
  }

  public storeFaena():void {
    
    this.faenaForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let faena: Faena = {
      rut: this.faenaForm.value.rut,
      name: this.faenaForm.value.name,
      address: this.faenaForm.value.address,
      city: this.faenaForm.value.city,
      contact: this.faenaForm.value.contact,
      phone: this.faenaForm.value.phone
    } as Faena;

    this._faenasService.storeFaena(this.cliente.id, faena)
    .subscribe(
      //Success request
      (response: any) => {

        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_faenasList();
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

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_faenasList();

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
              'Error al intentar guardar la faena',
              NotificationsService.messageType.error
            );

            break;
          }
        }

        this.faenaForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_faenasList(): void {
    this.router.navigate([`/panel/clientes/${this.cliente.id}/faenas`]);
  }

}
