import { Component, OnInit } from '@angular/core';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { Faena } from 'src/app/interfaces/faena';
import { Proveedor } from 'src/app/interfaces/proveedor';
import { ClientesService } from 'src/app/services/clientes.service';
import { CompradoresService } from 'src/app/services/compradores.service';
import { FaenasService } from 'src/app/services/faenas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ProveedoresService } from 'src/app/services/proveedores.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class ProveedoresEditComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];

  comprador: any = {
    id: null,
    name: null,
  };

  private sub: any;
  private id: number = -1;

  proveedorForm: FormGroup = new FormGroup({
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
    private _proveedoresService: ProveedoresService,
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.id = params['id'];
      this.comprador.id = params['comprador_id'];
      this.loadProveedor();
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(proveedorData: any)
  {
    this.comprador = proveedorData.comprador;
    this.proveedorForm.controls.rut.setValue(proveedorData.rut);
    this.proveedorForm.controls.name.setValue(proveedorData.name);
    this.proveedorForm.controls.address.setValue(proveedorData.address);
    this.proveedorForm.controls.city.setValue(proveedorData.city);
    this.proveedorForm.controls.contact.setValue(proveedorData.contact);
    this.proveedorForm.controls.phone.setValue(proveedorData.phone);
  }

  public loadProveedor(): void {
    
    this.loading = true;
    this.proveedorForm.disable();
    this._proveedoresService.getProveedor(this.comprador.id, this.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;
        this.loadFormData(response.data);
        this.proveedorForm.enable();
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
              'Error al cargar los datos del proveedor',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_proveedoresList();
      }
    );
  }

  public updateProveedor()
  {
    this.proveedorForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let proveedor: Proveedor = {
      rut: this.proveedorForm.value.rut,
      name: this.proveedorForm.value.name,
      address: this.proveedorForm.value.address,
      city: this.proveedorForm.value.city,
      contact: this.proveedorForm.value.contact,
      phone: this.proveedorForm.value.phone
    } as Proveedor;
    
    this._proveedoresService.updateProveedor(this.comprador.id, this.id, proveedor)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_proveedoresList();
      },
      //Error request
      (errorResponse: any) => {
        switch(errorResponse.status)
        {
          case 400: //Bad request
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

          case 412: //Object not found
          {
            NotificationsService.showToast(
              errorResponse.error.message,
              NotificationsService.messageType.warning
            );

            this.goTo_proveedoresList();

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
              'Error al actualizar el proveedor',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.proveedorForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_proveedoresList()
  {
    this.router.navigate([`/panel/compradores/${this.comprador.id}/proveedores`]);
  }

}
