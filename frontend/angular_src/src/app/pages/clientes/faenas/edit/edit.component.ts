import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Faena } from 'src/app/interfaces/faena';
import { ClientesService } from 'src/app/services/clientes.service';
import { FaenasService } from 'src/app/services/faenas.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class FaenasEditComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];

  cliente: any = {
    id: null,
    name: null,
  };

  private sub: any;
  private id: number = -1;

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
    private _faenasService: FaenasService,
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.id = params['id'];
      this.cliente.id = params['cliente_id'];
      this.loadFaena();
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(faenaData: any)
  {
    this.cliente = faenaData.cliente;
    this.faenaForm.controls.rut.setValue(faenaData.rut);
    this.faenaForm.controls.name.setValue(faenaData.name);
    this.faenaForm.controls.address.setValue(faenaData.address);
    this.faenaForm.controls.city.setValue(faenaData.city);
    this.faenaForm.controls.contact.setValue(faenaData.contact);
    this.faenaForm.controls.phone.setValue(faenaData.phone);
  }

  public loadFaena(): void {
    
    this.loading = true;
    this.faenaForm.disable();
    this._faenasService.getFaena(this.cliente.id, this.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;
        this.loadFormData(response.data);
        this.faenaForm.enable();
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
              'Error al cargar los datos de la faena',
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

  public updateFaena()
  {
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
    
    this._faenasService.updateFaena(this.cliente.id, this.id, faena)
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
              'Error al actualizar la faena',
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

  public goTo_faenasList()
  {
    this.router.navigate([`/panel/clientes/${this.cliente.id}/faenas`]);
  }

}
