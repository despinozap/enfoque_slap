import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ParametersService } from 'src/app/services/parameters.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class ParametersEditComponent implements OnInit {

  loading: boolean = false;
  responseErrors: any = [];
  
  private sub: any;
  parameter: any = {
    id: -1,
    description: ''
  };

  parameterForm: FormGroup = new FormGroup({
    value: new FormControl('', [Validators.required]),
  });
  
  constructor(
    private route: ActivatedRoute, 
    private _parametersService: ParametersService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      
      this.parameter.id = params['id'];
      this.parameterForm.disable();
      this.loading = true;

      this._parametersService.getParameter(this.parameter.id)
      .subscribe(
        //Success request
        (response: any) => {
          this.parameter.description = response.data.description;
          this.parameterForm.enable();
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
                'Error al cargar los datos del parametro',
                NotificationsService.messageType.error
              );
    
              break;

            }
          }

          this.loading = false;
          this.goTo_parametersList();
        }
      );
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(parameterData: any)
  {
    this.parameterForm.controls.value.setValue(parameterData.value);
  }

  public updateParameter()
  {
    this.parameterForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let parameter: any = {
      value: this.parameterForm.value.value,
    };
    
    this._parametersService.updateParameter(this.parameter.id, parameter)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_parametersList();
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

            this.goTo_parametersList();

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
              'Error al actualizar el parametro',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.parameterForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_parametersList()
  {
    this.router.navigate(['/panel/parameters']);
  }

}
