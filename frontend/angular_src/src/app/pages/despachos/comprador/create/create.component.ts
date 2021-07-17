import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { Centrodistribucion } from 'src/app/interfaces/centrodistribucion';
import { DespachosService } from 'src/app/services/despachos.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class DespachosCompradorCreateComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };  

  
  dtTrigger: Subject<any> = new Subject<any>();

  centrosdistribucion: Array<Centrodistribucion> = null as any;
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  despachoForm: FormGroup = new FormGroup({
    centrodistribucion: new FormControl('', [Validators.required]),
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  comprador_id: number = 1;

  constructor(
    private location: Location,
    private router: Router,
    private _despachosService: DespachosService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
        this.loadCentrosdistribucion();

        this.despachoForm.controls.fecha.setValue(this.getDateToday());
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
  
  public loadCentrosdistribucion(): void {
    
    this.loading = true;
    this.despachoForm.disable();

    this._despachosService.prepareStoreDespacho_comprador(this.comprador_id)
    .subscribe(
      //Success request
      (response: any) => {

        if(response.data.centrosdistribucion.length > 0) 
        {
          // Centrosdistribucion
          this.centrosdistribucion = response.data.centrosdistribucion;

          this.loading = false;
          this.despachoForm.enable();
        }
        else
        {
          NotificationsService.showToast(
            'No se encontraron centros de distribucion con partes pendiente de despacho',
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
              'Error al cargar los datos de los centros de distribucion',
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
    this.despachoForm.disable();

    this._despachosService.queueOcPartesDespacho_comprador(this.comprador_id, this.despachoForm.value.centrodistribucion)
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
                cantidad_stock: ocparte.cantidad_recepcionado - ocparte.cantidad_despachado,
                cantidad: ocparte.cantidad_recepcionado - ocparte.cantidad_despachado,
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
            'No se encontraron partes para despachar al centro de distribucion seleccionado',
            NotificationsService.messageType.info
          );
        }

        this.loading = false;
        this.despachoForm.enable();
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
              'Error al cargar los datos de las partes para despachar',
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

  public storeDespacho(): void {
    this.despachoForm.disable();
    this.loading = true;
    this.responseErrors = [];

    // Prepare OCs list
    let indexOc;
    let indexParte;
    let dispatchedOcs = this.partes.reduce((carry, parte) =>
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

    let despacho: any = {
      centrodistribucion_id: this.despachoForm.value.centrodistribucion,
      fecha: this.despachoForm.value.fecha,
      ndocumento: this.despachoForm.value.documento,
      responsable: this.despachoForm.value.responsable,
      comentario: this.despachoForm.value.comentario,
      ocs: dispatchedOcs
    };

    this._despachosService.storeDespacho_comprador(this.comprador_id, despacho)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_despachosList();
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
                  'Error al intentar guardar el despacho',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.despachoForm.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0) && (parseInt(evt.target.value) <= parte.cantidad_stock))
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_stock;
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

  public goTo_despachosList(): void {
    this.router.navigate(['/panel/despachos/comprador']);
  }

  public goTo_back(): void {
    this.location.back();
  }

}
