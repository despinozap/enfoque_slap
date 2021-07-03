import { Component, OnInit, ViewChild } from '@angular/core';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class RecepcionesCompradorEditComponent implements OnInit {

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

  comprador_id: number = 1;
  recepcion: any = {
    id: -1,
    oc_id: -1,
    oc_noccliente: null,
    fecha: null,
    ndocumento: null,
    responsable: null,
    comentario: null,
    created_at: null,
    proveedor_name: null,
    cliente_name: null,
    faena_name: null
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  recepcionForm: FormGroup = new FormGroup({
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  private sub: any;
  
  
  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _recepcionesService: RecepcionesService,
    private _utilsService: UtilsService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.recepcion.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadRecepcion();
    },
    100);
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
    this.dtTrigger.unsubscribe();
  }

  private renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }

  private loadFormData(recepcionData: any)
  { 
    if(recepcionData.ocpartes.length > 0)
    {
      // All Partes in OC
      this.partes = recepcionData.oc.partes.reduce((carry: any[], parte: any) => {

        carry.push({
          id: parte.id,
          nparte: parte.nparte,
          descripcion: parte.pivot.descripcion,
          marca: parte.marca.name,
          backorder: parte.backorder > 0 ? true : false,
          cantidad: parte.pivot.cantidad - parte.pivot.cantidad_recepcionado,
          cantidad_total: parte.pivot.cantidad,
          cantidad_recepcionado: parte.pivot.cantidad_recepcionado,
          cantidad_pendiente: parte.pivot.cantidad - parte.pivot.cantidad_recepcionado,
          cantidad_min: parte.pivot.cantidad_despachado - (parte.pivot.cantidad_recepcionado - parte.pivot.cantidad),
          checked: false
        });
        
          return carry;
        },
        [] // Empty array
      );

      this.recepcion.id = recepcionData.id;
      this.recepcion.oc_id = recepcionData.oc.id;
      this.recepcion.oc_noccliente = recepcionData.oc.noccliente;
      this.recepcion.fecha = recepcionData.fecha;
      this.recepcion.ndocumento = recepcionData.ndocumento;
      this.recepcion.responsable = recepcionData.responsable;
      this.recepcion.comentario = recepcionData.comentario;
      this.recepcion.created_at = recepcionData.created_at;
      this.recepcion.proveedor_name = recepcionData.sourceable.name;
      this.recepcion.cliente_name = recepcionData.oc.cotizacion.solicitud.faena.cliente.name;
      this.recepcion.faena_name = recepcionData.oc.cotizacion.solicitud.faena.name;

      this.recepcionForm.controls.fecha.setValue(this.dateStringFormat(recepcionData.fecha));
      this.recepcionForm.controls.documento.setValue(recepcionData.ndocumento);
      this.recepcionForm.controls.responsable.setValue(recepcionData.responsable);
      this.recepcionForm.controls.comentario.setValue(recepcionData.comentario);
      
      // Load Partes in Recepcion
      recepcionData.ocpartes.forEach((ocParte: any) => {
        let index = this.partes.findIndex((p) => {
          if(p.id === ocParte.parte.id)
          {
            return true;
          }
          else
          {
            return false;
          }
        });

        if(index >= 0)
        {
          // Update data for parte in Recepcion
          this.partes[index].checked = true;
          this.partes[index].cantidad = ocParte.pivot.cantidad;
          this.partes[index].cantidad_pendiente = this.partes[index].cantidad_pendiente + ocParte.pivot.cantidad;
          this.partes[index].cantidad_recepcionado = this.partes[index].cantidad_recepcionado - ocParte.pivot.cantidad;
        }
      });

      // Clean partes with no cantidad_pendiente in list
      this.partes = this.partes.filter((parte: any) => {
        if(parte.cantidad_pendiente > 0)
        {
          return true;
        }
        else
        {
          return false;
        }
      });

      // Sort partes pushing checked ones to the top
      this.partes = this.partes.sort((p1, p2) => {
        return ((p2.checked === true) ? 1 : 0) - ((p1.checked === true) ? 1 : 0);
      });
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_recepcionesList();
    }
  }

  public loadRecepcion(): void {
    
    this.loading = true;

    this._recepcionesService.prepareUpdateRecepcion_comprador(this.comprador_id, this.recepcion.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loadFormData(response.data);
        this.renderDataTable(this.datatableElement_partes);

        this.recepcionForm.enable();
        this.loading = false;
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
              'Error al cargar los datos de la recepcion',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_recepcionesList();
      }
    );
  }

  public updateRecepcion(): void {
    this.recepcionForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let receivedPartes = this.partes.reduce((carry, parte) => 
      {
        if(parte.checked === true)
        {
          carry.push(
            {
              id: parte.id,
              cantidad: parte.cantidad
            }
          );
        }
      
        return carry;
      }, 
      []
    );

    let recepcion: any = {
      fecha: this.recepcionForm.value.fecha,
      ndocumento: this.recepcionForm.value.documento,
      responsable: this.recepcionForm.value.responsable,
      comentario: this.recepcionForm.value.comentario,
      partes: receivedPartes
    };

    this._recepcionesService.updateRecepcion_comprador(this.comprador_id, this.recepcion.id, recepcion)
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

            case 409: //Permission denied
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
                  'Error al intentar actualizar la recepcion',
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
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0) && (parseInt(evt.target.value) <= parte.cantidad_pendiente))
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_pendiente;
    }
  }

  public checkPartesList(evt: any): void {
    this.partes.forEach((parte: any) => {
      parte.checked = evt.target.checked;
    });
  }

  public checkParteItem(parte: any, evt: any): void {
    parte.checked = evt.target.checked;
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
    this.router.navigate(['/panel/recepciones/comprador']);
  }

}
