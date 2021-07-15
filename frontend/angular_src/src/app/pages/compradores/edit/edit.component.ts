import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Comprador } from 'src/app/interfaces/comprador';
import { CompradoresService } from 'src/app/services/compradores.service';
import { NotificationsService } from 'src/app/services/notifications.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class CompradoresEditComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;
  private id: number = -1;

  compradorForm: FormGroup = new FormGroup({
    rut: new FormControl('', [Validators.required, Validators.minLength(4)]),
    name: new FormControl('', [Validators.required, Validators.minLength(4)]),
    address: new FormControl('', [Validators.required, Validators.minLength(4)]),
    city: new FormControl('', [Validators.required, Validators.minLength(4)]),
    email: new FormControl('', [Validators.required, Validators.email]),
    phone: new FormControl('', [Validators.required, Validators.minLength(4)])
  });
  
  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _compradoresService: CompradoresService,
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.id = params['id'];
      this.loadComprador();
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(compradorData: any)
  {
    this.compradorForm.controls.rut.setValue(compradorData.rut);
    this.compradorForm.controls.name.setValue(compradorData.name);
    this.compradorForm.controls.address.setValue(compradorData.address);
    this.compradorForm.controls.city.setValue(compradorData.city);
    this.compradorForm.controls.email.setValue(compradorData.email);
    this.compradorForm.controls.phone.setValue(compradorData.phone);
  }

  public loadComprador(): void {
    
    this.loading = true;
    this.compradorForm.disable();
    this._compradoresService.getComprador(this.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;
        this.loadFormData(response.data);
        this.compradorForm.enable();
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
        this.goTo_compradoresList();
      }
    );
  }

  public updateComprador()
  {
    this.compradorForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let comprador: Comprador = {
      rut: this.compradorForm.value.rut,
      name: this.compradorForm.value.name,
      address: this.compradorForm.value.address,
      city: this.compradorForm.value.city,
      email: this.compradorForm.value.email,
      phone: this.compradorForm.value.phone
    } as Comprador;
    
    this._compradoresService.updateComprador(this.id, comprador)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_compradoresList();
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

            this.goTo_compradoresList();

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
              'Error al actualizar el comprador',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.compradorForm.enable();
        this.loading = false;
      }
    );
  }
  
  public goTo_compradoresList()
  {
    this.router.navigate([`/panel/compradores`]);
  }

}
