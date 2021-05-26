import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
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
    private _compradoresService: CompradoresService,
    private _proveedoresService: ProveedoresService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.comprador.id = params['comprador_id'];
      this.loadComprador();
    });
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

  public storeProveedor():void {
    
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
