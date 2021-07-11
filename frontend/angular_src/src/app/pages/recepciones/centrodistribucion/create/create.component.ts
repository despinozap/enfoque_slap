import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { Comprador } from 'src/app/interfaces/comprador';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class RecepcionesCentrodistribucionCreateComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };  

  
  dtTrigger: Subject<any> = new Subject<any>();

  compradores: Array<Comprador> = null as any;
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  recepcionForm: FormGroup = new FormGroup({
    comprador: new FormControl('', [Validators.required]),
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  centrodistribucion_id: number = 1;

  constructor(
    private location: Location,
    private router: Router,
    private _recepcionesService: RecepcionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
        this.loadCompradores();

        this.recepcionForm.controls.fecha.setValue(this.getDateToday());
      },
      100
    );
  }

  ngOnDestroy(): void {
    this.dtTrigger.unsubscribe();
  }
  
  private renderDataTable(dataTableElement: DataTableDirective, trigger: Subject<any>): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the trigger to rerender again
      trigger.next();
    });
  }
  
  public loadCompradores(): void {
    
    this.loading = true;
    this.recepcionForm.disable();

    this._recepcionesService.prepareStoreRecepcion_centrodistribucion(this.centrodistribucion_id)
    .subscribe(
      //Success request
      (response: any) => {

        if(response.data.compradores.length > 0) 
        {
          // Compradores
          this.compradores = response.data.compradores;

          this.loading = false;
          this.recepcionForm.enable();
        }
        else
        {
          NotificationsService.showToast(
            'No se encontraron compradores con partes pendiente de recepcion',
            NotificationsService.messageType.info
          );

          this.loading = false;
          this.goTo_back();
        }
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
              'Error al cargar los datos de los compradores',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_back();
      }
    );
  }

  public loadOcPartes(): void {
    
    this.loading = true;
    this.recepcionForm.disable();

    this._recepcionesService.getQueueOcPartes_centrodistribucion(this.centrodistribucion_id, this.recepcionForm.value.comprador)
    .subscribe(
      //Success request
      (response: any) => {

        if(response.data.length > 0)
        {
          // OcPartes
          this.partes = response.data.reduce((carry: any[], ocparte: any) => {
              
            carry.push(
              {
                id: ocparte.parte.id,
                descripcion: ocparte.descripcion,
                nparte: ocparte.parte.nparte,
                marca_name: ocparte.parte.marca.name,
                oc_id: ocparte.oc.id,
                oc_noccliente: ocparte.oc.noccliente,
                backorder: ocparte.backorder === 1 ? true : false,
                sucursal_name: ocparte.oc.cotizacion.solicitud.sucursal.name,
                faena_name: ocparte.oc.cotizacion.solicitud.faena.name,
                cantidad_transit: ocparte.cantidad_despachado - ocparte.cantidad_recepcionado,
                cantidad: ocparte.cantidad_despachado - ocparte.cantidad_recepcionado,
                checked: false
              }
            );
  
              return carry;
            },
            [] // Empty array
          );
  
          this.renderDataTable(this.datatableElement_partes, this.dtTrigger);
        }
        else
        {
          NotificationsService.showAlert(
            'No se encontraron partes para recepcionar desde el comprador seleccionado',
            NotificationsService.messageType.info
          );
        }

        this.loading = false;
        this.recepcionForm.enable();
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
              'Error al cargar los datos de las partes para recepcionar',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_back();
      }
    );
  }

  public storeRecepcion(): void {
    this.recepcionForm.disable();
    this.loading = true;
    this.responseErrors = [];

    // Prepare OCs list
    let indexOc;
    let indexParte;
    let receivedOcs = this.partes.reduce((carry, parte) =>
      {
        if(parte.checked === true)
        {
          indexOc = carry.findIndex((oc: any) => {
            return (oc.id === parte.oc_id);
          });
  
          // If Oc already exists in list
          if(indexOc >= 0)
          {
            indexParte = carry[indexOc].partes.findIndex((p: any) => {
              return (p.id === parte.id);
            });
  
            // If Parte is already in the partes list for Oc
            if(indexParte >= 0)
            {
              // Adds cantidad to the existing parte
              carry[indexOc].partes[indexParte] += parte.cantidad;
            }
            // If Parte isn't in the partes list for Oc
            else
            {
              // Add Parte to the partes list in Oc
              carry[indexOc].partes.push(
                {
                  id: parte.id,
                  cantidad: parte.cantidad
                }
              );
            }
          }
          // If doesn't exist
          else
          {
            // Add the OC to list and also add the parte in partes list for the Oc
            carry.push(
              {
                id: parte.oc_id,
                partes: [
                  {
                    id: parte.id,
                    cantidad: parte.cantidad
                  }
                ]
              }
            );
          }

        }

        return carry;
      },
      []
    );

    let recepcion: any = {
      comprador_id: this.recepcionForm.value.comprador,
      fecha: this.recepcionForm.value.fecha,
      ndocumento: this.recepcionForm.value.documento,
      responsable: this.recepcionForm.value.responsable,
      comentario: this.recepcionForm.value.comentario,
      ocs: receivedOcs
    };

    this._recepcionesService.storeRecepcion_centrodistribucion(this.centrodistribucion_id, recepcion)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_recepcionesList();
        },
        //Error request
        (errorResponse: any) => {

          switch (errorResponse.status) 
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
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );
    
                break;
              }

            case 412: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.error
                );

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
                  'Error al intentar guardar la recepcion',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.recepcionForm.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if(
        (isNaN(evt.target.value) === false) && 
        (parseInt(evt.target.value) > 0) && 
        (parseInt(evt.target.value) <= parte.cantidad_transit)
    )
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_transit;
    }
  }

  public checkPartesList(evt: any): void {
    this.partes.forEach((parte: any) => {
      parte.checked = evt.target.checked;
    });

    this.sortPartesByChecked();
  }

  public checkParteItem(parte: any, evt: any): void {
    parte.checked = evt.target.checked;

    this.sortPartesByChecked();
  }

  private sortPartesByChecked(): void {
    // Sort partes pushing checked ones to the top
    this.partes = this.partes.sort((p1, p2) => {
      return ((p2.checked === true) ? 1 : 0) - ((p1.checked === true) ? 1 : 0);
    });
  }

  public isCheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === true)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }

  public isUncheckedItem(dataSource: any[]): boolean
  {
    let index = dataSource.findIndex((e) => {
      if(e.checked === false)
      {
        return true;
      }
      else
      {
        return false;
      }
    });

    return index >= 0 ? true : false;
  }
  
  public getDateToday(): string {
    let dt = new Date();

    return this.dateStringFormat(`${dt.getFullYear()}-${(dt.getMonth() + 1)}-${dt.getDate()}`);
  }

  private dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public goTo_recepcionesList(): void {
    this.router.navigate(['/panel/recepciones/centrodistribucion']);
  }

  public goTo_back(): void {
    this.location.back();
  }

}
