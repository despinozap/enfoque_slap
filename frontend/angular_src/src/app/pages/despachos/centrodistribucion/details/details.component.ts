import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { DespachosService } from 'src/app/services/despachos.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class DespachosCentrodistribucionDetailsComponent implements OnInit {

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
  
  centrodistribucion_id: number = 1;
  despacho: any = {
    id: -1,
    fecha: null,
    ndocumento: null,
    responsable: null,
    comentario: null,
    created_at: null,
    centrodistribucion_name: null,
    sucursal_name: null,
  };
  
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private route: ActivatedRoute,
    private _despachosService: DespachosService,
    private location: Location,
    private _utilsService: UtilsService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.despacho.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadDespacho();
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

  private loadFormData(despachoData: any)
  { 
    if(despachoData['ocpartes'].length > 0)
    {
      this.despacho.id = despachoData.id;
      this.despacho.fecha = despachoData.fecha;
      this.despacho.ndocumento = despachoData.ndocumento;
      this.despacho.responsable = despachoData.responsable;
      this.despacho.comentario = despachoData.comentario;
      this.despacho.created_at = despachoData.created_at;
      this.despacho.centrodistribucion_name = despachoData.despachable.name;
      this.despacho.sucursal_name = despachoData.destinable.name;
      
      this.partes = despachoData.ocpartes.map((ocparte: any) => 
        {
          return {
            id: ocparte.parte.id,
            descripcion: ocparte.descripcion,
            nparte: ocparte.parte.nparte,
            marca_name: ocparte.parte.marca.name,
            oc_id: ocparte.oc.id,
            oc_noccliente: ocparte.oc.noccliente,
            backorder: ocparte.backorder === 1 ? true : false,
            sucursal_name: ocparte.oc.cotizacion.solicitud.sucursal.name,
            faena_name: ocparte.oc.cotizacion.solicitud.faena.name,
            cantidad: ocparte.pivot.cantidad,
            checked: false
          };
        }
      );

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

  public loadDespacho(): void {
    
    this.loading = true;

    this._despachosService.getDespacho_centrodistribucion(this.centrodistribucion_id, this.despacho.id)
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
              'Error al cargar los datos del despacho',
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
