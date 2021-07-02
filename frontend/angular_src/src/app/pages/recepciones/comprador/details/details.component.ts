import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Location } from '@angular/common';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class RecepcionesCompradorDetailsComponent implements OnInit {

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

  private sub: any;

  constructor(
    private route: ActivatedRoute,
    private _recepcionesService: RecepcionesService,
    private location: Location,
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

  ngOnDestroy(): void {
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
      
      this.partes = [];
      recepcionData.ocpartes.forEach((ocParte: any) => {
        this.partes.push(
          {
            'id': ocParte.parte.id,
            'nparte': ocParte.parte.nparte,
            'descripcion': ocParte.descripcion,
            'marca_name': ocParte.parte.marca.name,
            'cantidad': ocParte.pivot.cantidad
          }
        )
      });
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_back();
    }
  }

  public loadRecepcion(): void {
    
    this.loading = true;

    this._recepcionesService.getRecepcion_comprador(this.comprador_id, this.recepcion.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loadFormData(response.data);
        this.renderDataTable(this.datatableElement_partes);

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
        this.goTo_back();
      }
    );
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public goTo_back(): void {
    this.location.back();
  }
}
