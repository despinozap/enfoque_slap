import { AfterViewInit, Component, OnInit } from '@angular/core';
import { FormGroup, FormControl, Validators, AbstractControl } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { Proveedor } from 'src/app/interfaces/proveedor';
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

  public deliveryFieldValidator(control: AbstractControl):{[key: string]: boolean} | null 
  {
    let errorMessages = {
      required: false,
      minlength: false
    };

    if(control.value === null)
    {
      errorMessages.required = true;
    }
    else
    {
      if(control.value.length === 0)
      {
        errorMessages.required = true;
      }
      
      if(control.value.length < 4)
      {
        errorMessages.minlength = true;
      }
    }
    
    // If any of validations is broken, then return errorMessages. Otherwise returns null (valid)
    return ((errorMessages.required === true) || (errorMessages.minlength === true)) ? errorMessages : null;
  }

  private loadFormData(proveedorData: any)
  {
    this.comprador = proveedorData.comprador;
    this.proveedorForm.controls.rut.setValue(proveedorData.rut);
    this.proveedorForm.controls.name.setValue(proveedorData.name);
    this.proveedorForm.controls.address.setValue(proveedorData.address);
    this.proveedorForm.controls.city.setValue(proveedorData.city);
    this.proveedorForm.controls.email.setValue(proveedorData.email);
    this.proveedorForm.controls.phone.setValue(proveedorData.phone);
    this.proveedorForm.controls.delivered.setValue(proveedorData.delivered == 0 ? false : true);
    this.proveedorForm.controls.delivery_name.setValue(proveedorData.delivery_name);
    this.proveedorForm.controls.delivery_address.setValue(proveedorData.delivery_address);
    this.proveedorForm.controls.delivery_city.setValue(proveedorData.delivery_city);
    this.proveedorForm.controls.delivery_email.setValue(proveedorData.delivery_email);
    this.proveedorForm.controls.delivery_phone.setValue(proveedorData.delivery_phone);

    this.updateStatusDelivered();
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
      email: this.proveedorForm.value.email,
      phone: this.proveedorForm.value.phone,
      delivered: this.proveedorForm.value.delivered,
      delivery_name: this.proveedorForm.value.delivery_name,
      delivery_address: this.proveedorForm.value.delivery_address,
      delivery_city: this.proveedorForm.value.delivery_city,
      delivery_email: this.proveedorForm.value.delivery_email,
      delivery_phone: this.proveedorForm.value.delivery_phone
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

  public updateStatusDelivered(): void {

    if(this.proveedorForm.value.delivered === true)
    {
      this.proveedorForm.controls.delivery_name.enable();
      this.proveedorForm.controls.delivery_address.enable();
      this.proveedorForm.controls.delivery_city.enable();
      this.proveedorForm.controls.delivery_email.enable();
      this.proveedorForm.controls.delivery_phone.enable();

      this.proveedorForm.controls.delivery_name.setValidators([this.deliveryFieldValidator, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_address.setValidators([this.deliveryFieldValidator, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_city.setValidators([this.deliveryFieldValidator, Validators.minLength(4)]);
      this.proveedorForm.controls.delivery_email.setValidators([this.deliveryFieldValidator, Validators.email]);
      this.proveedorForm.controls.delivery_phone.setValidators([this.deliveryFieldValidator, Validators.minLength(4)]);

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
  
  public goTo_proveedoresList()
  {
    this.router.navigate([`/panel/compradores/${this.comprador.id}/proveedores`]);
  }

}
