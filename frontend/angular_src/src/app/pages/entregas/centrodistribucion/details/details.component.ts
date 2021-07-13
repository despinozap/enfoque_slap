import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { EntregasService } from 'src/app/services/entregas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class EntregasCentrodistribucionDetailsComponent implements OnInit {

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
  
  centrodistribucion_id: number = 1;
  entrega: any = {
    id: -1,
    fecha: null,
    ndocumento: null,
    responsable: null,
    comentario: null,
    created_at: null,
    centrodistribucion_name: null,
    oc_id: null,
    noccliente: null,
    cliente_name: null,
    faena_name: null,
  };
  
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private route: ActivatedRoute,
    private _entregasService: EntregasService,
    private location: Location,
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

  private loadFormData(entregaData: any)
  { 
    if(entregaData['ocpartes'].length > 0)
    {
      this.entrega.id = entregaData.id;
      this.entrega.fecha = entregaData.fecha;
      this.entrega.ndocumento = entregaData.ndocumento;
      this.entrega.responsable = entregaData.responsable;
      this.entrega.comentario = entregaData.comentario;
      this.entrega.created_at = entregaData.created_at;
      this.entrega.centrodistribucion_name = entregaData.oc.cotizacion.solicitud.sucursal.name;
      this.entrega.oc_id = entregaData.oc.id;
      this.entrega.noccliente = entregaData.oc.noccliente;
      this.entrega.cliente_name = entregaData.oc.cotizacion.solicitud.faena.cliente.name;
      this.entrega.faena_name = entregaData.oc.cotizacion.solicitud.faena.name;
      
      this.partes = [];
      entregaData.ocpartes.forEach((ocparte: any) => {
        this.partes.push(
          {
            'id': ocparte.parte.id,
            'nparte': ocparte.parte.nparte,
            'marca': ocparte.parte.marca,
            'descripcion': ocparte.descripcion,
            'backorder': ocparte.backorder > 0 ? true : false,
            'cantidad': ocparte.pivot.cantidad,
            'cantidad_pendiente': ocparte.cantidad - ocparte.cantidad_entregado,
            'estadoocparte': ocparte.estadoocparte 
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

  public loadEntrega(): void {
    
    this.loading = true;

    this._entregasService.getEntrega_centrodistribucion(this.centrodistribucion_id, this.entrega.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loadFormData(response.data);
        this.renderDataTable(this.datatableElement_ocpartes);

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
              'Error al cargar los datos de la entrega',
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
