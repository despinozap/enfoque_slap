import { Component, OnInit, ViewChild } from '@angular/core';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { EntregasService } from 'src/app/services/entregas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EntregasCentrodistribucionEditComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_ocpartes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };

  dtTrigger: Subject<any> = new Subject<any>();

  entrega: any = {
    id: -1,
    centrodistribucion_id: -1,
    centrodistribucion_name: null,
    oc_id: null,
    noccliente: null,
    cliente_name: null,
    faena_name: null,
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  entregaForm: FormGroup = new FormGroup({
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  private sub: any;
  centrodistribucion_id: number = 1;
  
  
  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _entregasService: EntregasService,
    private _utilsService: UtilsService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.entrega.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadEntrega();
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

  private loadFormData(entregaData: any)
  {
    if(entregaData.entrega['ocpartes'].length > 0)
    {
      // Load Entrega data
      this.entrega.id = entregaData.entrega.id;
      this.entrega.centrodistribucion_id = entregaData.entrega.oc.cotizacion.solicitud.sucursal.id;
      this.entrega.centrodistribucion_name = entregaData.entrega.oc.cotizacion.solicitud.sucursal.name;
      this.entrega.oc_id = entregaData.entrega.oc.id;
      this.entrega.noccliente = entregaData.entrega.oc.noccliente;
      this.entrega.cliente_name = entregaData.entrega.oc.cotizacion.solicitud.faena.cliente.name;
      this.entrega.faena_name = entregaData.entrega.oc.cotizacion.solicitud.faena.name;

      this.entregaForm.controls.fecha.setValue(this.dateStringFormat(entregaData.entrega.fecha));
      if(entregaData.entrega.ndocumento !== null)
      {
        this.entregaForm.controls.documento.setValue(entregaData.entrega.ndocumento);
      }
      this.entregaForm.controls.responsable.setValue(entregaData.entrega.responsable);
      if(entregaData.entrega.comentario !== null)
      {
        this.entregaForm.controls.comentario.setValue(entregaData.entrega.comentario);
      }

      let cantidad_pendiente: number;
      let cantidad_max: number;

      // Load partes list from queue_partes
      this.partes = entregaData.queue_partes.reduce((carry: any[], parte: any) => {

          cantidad_pendiente = parte.cantidad_total - parte.cantidad_entregado;
          cantidad_max = cantidad_pendiente <= parte.cantidad_stock ? cantidad_pendiente : parte.cantidad_stock;

          carry.push({
            id: parte.id,
            nparte: parte.nparte,
            marca: parte.marca,
            backorder: parte.backorder > 0 ? true : false,
            cantidad: cantidad_max,
            cantidad_total: parte.cantidad_total,
            cantidad_pendiente: cantidad_pendiente,
            cantidad_stock: parte.cantidad_stock,
            cantidad_entregado: parte.cantidad_entregado,
            cantidad_max: cantidad_max,
            checked: false,
          });

          return carry;
        },
        [] // Empty array
      );

      let index: number;

      // Update values with partes list in entrega 
      entregaData.entrega.ocpartes.forEach((ocparte: any) => {

        index = this.partes.findIndex((parte) => {
          return (parte.id === ocparte.parte.id);
        });

        if(index >= 0)
        {
          cantidad_pendiente = this.partes[index].cantidad_total - this.partes[index].cantidad_entregado + ocparte.pivot.cantidad;
          cantidad_max = cantidad_pendiente <= this.partes[index].cantidad_stock ? cantidad_pendiente : this.partes[index].cantidad_stock;

          this.partes[index].checked = true;
          this.partes[index].cantidad = ocparte.pivot.cantidad;
          this.partes[index].cantidad_pendiente = cantidad_pendiente;
          this.partes[index].cantidad_max = cantidad_max;
        }

      });

      this.partes = this.partes.sort((p1, p2) => {
        return p2.cantidad - p1.cantidad;
      });

      this.renderDataTable(this.datatableElement_ocpartes);
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_entregasList();
    }
  }

  public loadEntrega(): void {
    
    this.loading = true;

    this._entregasService.prepareUpdateEntrega_centrodistribucion(this.centrodistribucion_id, this.entrega.id)
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
              'Error al cargar los datos de la entrega',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_entregasList();
      }
    );
  }

  public updateEntrega(): void {
    this.entregaForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let deliveredPartes = this.partes.reduce((carry, parte) => 
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

    let entrega: any = {
      fecha: this.entregaForm.value.fecha,
      ndocumento: this.entregaForm.value.documento,
      responsable: this.entregaForm.value.responsable,
      comentario: this.entregaForm.value.comentario,
      partes: deliveredPartes
    };

    this._entregasService.updateEntrega_centrodistribucion(this.centrodistribucion_id, this.entrega.id, entrega)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_entregasList();
        },
        //Error request
        (errorResponse: any) => {

          console.log(errorResponse);
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
                  'Error al intentar actualizar la entrega',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.entrega.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if(
        (isNaN(evt.target.value) === false) && 
        (parseInt(evt.target.value) > 0) && 
        (parseInt(evt.target.value) <= parte.cantidad_stock)
    )
    {
        parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad;
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

  public goTo_entregasList(): void {
    this.router.navigate(['/panel/entregas/centrodistribucion']);
  }

}
