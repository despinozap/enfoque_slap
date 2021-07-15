import { ifStmt } from '@angular/compiler/src/output/output_ast';
import { Component, OnInit } from '@angular/core';
import { AbstractControl, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { Proveedor } from 'src/app/interfaces/proveedor';
import { CompradoresService } from 'src/app/services/compradores.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ProveedoresService } from 'src/app/services/proveedores.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class ProveedoresCreateComponent implements OnInit {

  comprador: any = {
    id: null,
    name: null,
  };

  proveedores: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  proveedorForm: FormGroup = new FormGroup({
    rut: new FormControl('', [Validators.required, Validators.minLength(4)]),
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    address: new FormControl('', [Validators.required, Validators.minLength(4)]),
    city: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.minLength(4)]),
    delivered: new FormControl(''),
    delivery_name: new FormControl(''),
    delivery_address: new FormControl(''),
    delivery_city: new FormControl(''),
    delivery_email: new FormControl(''),
    delivery_phone: new FormControl('')
  });

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _compradoresService: CompradoresService,
    private _proveedoresService: ProveedoresService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.comprador.id = params['comprador_id'];
      this.loadComprador();
    });

    this.proveedorForm.controls.delivered.setValue(true);
    this.updateStatusDelivered();
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(compradorData: any)
  {
    this.comprador.name = compradorData.name;
    this.proveedores = compradorData.proveedores;
  }

  public loadComprador(): void {
    
    this.loading = true;

    this._compradoresService.getComprador(this.comprador.id)
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
              'Error al cargar los datos del comprador',
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

  public updateStatusDelivered(): void {

    if(this.proveedorForm.value.delivered === true)
    {
      this.proveedorForm.controls.delivery_name.enable();
      this.proveedorForm.controls.delivery_address.enable();
      this.proveedorForm.controls.delivery_city.enable();
      this.proveedorForm.controls.delivery_email.enable();
      this.proveedorForm.controls.delivery_phone.enable();

      this.proveedorForm.controls.delivery_name.setValidators([Validators.required, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_address.setValidators([Validators.required, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_city.setValidators([Validators.required, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_email.setValidators([Validators.required, Validators.email]);
      this.proveedorForm.controls.delivery_phone.setValidators([Validators.required, Validators.minLength(4)]);

      // Add required field asterisk on labels
      document.querySelectorAll('.delivery-field label').forEach((el) => {
        el.className += " required-field";
      });
    }
    else
    {
      this.proveedorForm.controls.delivery_name.setValue('');
      this.proveedorForm.controls.delivery_name.disable();
      this.proveedorForm.controls.delivery_address.setValue('');
      this.proveedorForm.controls.delivery_address.disable();
      this.proveedorForm.controls.delivery_city.setValue('');
      this.proveedorForm.controls.delivery_city.disable();
      this.proveedorForm.controls.delivery_email.setValue('');
      this.proveedorForm.controls.delivery_email.disable();
      this.proveedorForm.controls.delivery_phone.setValue('');
      this.proveedorForm.controls.delivery_phone.disable();

      this.proveedorForm.controls.delivery_name.clearValidators();
      this.proveedorForm.controls.delivery_address.clearValidators();
      this.proveedorForm.controls.delivery_city.clearValidators();
      this.proveedorForm.controls.delivery_email.clearValidators();
      this.proveedorForm.controls.delivery_phone.clearValidators();

      // Remove required field asterisk on lables
      document.querySelectorAll('.delivery-field label').forEach((el) => {
        el.className = el.className.replace(' required-field', '');
      });
    }

    this.proveedorForm.controls.delivery_name.updateValueAndValidity();
    this.proveedorForm.controls.delivery_address.updateValueAndValidity();
    this.proveedorForm.controls.delivery_city.updateValueAndValidity();
    this.proveedorForm.controls.delivery_email.updateValueAndValidity();
    this.proveedorForm.controls.delivery_phone.updateValueAndValidity();
  }

  public storeProveedor():void {
    
    this.proveedorForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let proveedor: Proveedor = {
      rut: this.proveedorForm.value.rut,
      name: this.proveedorForm.value.name,
      address: this.proveedorForm.value.address,
      city: this.proveedorForm.value.city,
      email: this.proveedorForm.value.email,
      phone: this.proveedorForm.value.phone,
      delivered: this.proveedorForm.value.delivered,
      delivery_name: this.proveedorForm.value.delivery_name,
      delivery_address: this.proveedorForm.value.delivery_address,
      delivery_city: this.proveedorForm.value.delivery_city,
      delivery_email: this.proveedorForm.value.delivery_email,
      delivery_phone: this.proveedorForm.value.delivery_phone
    } as Proveedor;

    console.log('PROVEEDOR', proveedor);

    this._proveedoresService.storeProveedor(this.comprador.id, proveedor)
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
        console.log(errorResponse);
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
              'Error al intentar guardar el proveedor',
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

  public goTo_proveedoresList(): void {
    this.router.navigate([`/panel/compradores/${this.comprador.id}/proveedores`]);
  }

}
