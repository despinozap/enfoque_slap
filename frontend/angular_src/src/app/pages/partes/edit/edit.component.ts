import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Marca } from 'src/app/interfaces/marca';
import { Parte } from 'src/app/interfaces/parte';
import { MarcasService } from 'src/app/services/marcas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { PartesService } from 'src/app/services/partes.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class PartesEditComponent implements OnInit {

  marcas: Array<Marca> = null as any;
  loading: boolean = false;
  responseErrors: any = [];
  
  private sub: any;
  private id: number = -1;

  parteForm: FormGroup = new FormGroup({
    nparte: new FormControl('', [Validators.required]),
    marca: new FormControl('', [Validators.required])
  });
  

  constructor(
    private route: ActivatedRoute, 
    private _marcasService: MarcasService,
    private _partesService: PartesService,
    private router: Router
  ) 
  {
  }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      
      this.id = params['id'];
      this.parteForm.disable();
      this.loading = true;

      this.loadMarcas();

      this._partesService.getParte(this.id)
      .subscribe(
        //Success request
        (response: any) => {
          this.parteForm.enable();
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
                'Error al cargar los datos de la parte',
                NotificationsService.messageType.error
              );
    
              break;

            }
          }

          this.loading = false;
          this.goTo_partesList();
        }
      );
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadMarcas()
  {
    this.loading = true;
    this._marcasService.getMarcas()
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;

        this.marcas = <Array<Marca>>(response.data);
        
        this.parteForm.enable();
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
              'Error al cargar la lista de marcas',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }
        
        this.marcas = null as any;
        this.loading = false;

        this.goTo_partesList();
      }
    );  
  }

  private loadFormData(parteData: any)
  {
    this.parteForm.controls.nparte.setValue(parteData.nparte);
    this.parteForm.controls.marca.setValue(parteData.marca.id);
  }

  public updateParte()
  {
    this.parteForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let parte: Parte = {
      nparte: this.parteForm.value.nparte,
      marca_id: this.parteForm.value.marca
    } as Parte;
    console.log(parte);
    
    this._partesService.updateParte(this.id, parte)
    .subscribe(
      //Success request
      (response: any) => {
        NotificationsService.showToast(
          response.message,
          NotificationsService.messageType.success
        );

        this.goTo_partesList();
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

            this.goTo_partesList();

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
              'Error al actualizar la parte',
              NotificationsService.messageType.error
            );

            break;
          }

        }

        this.parteForm.enable();
        this.loading = false;
      }
    );
  }

  public goTo_partesList()
  {
    this.router.navigate(['/panel/partes']);
  }
}
