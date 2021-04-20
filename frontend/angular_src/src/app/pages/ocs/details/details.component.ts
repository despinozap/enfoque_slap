import { Component, OnInit, ViewChild } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { OcsService } from 'src/app/services/ocs.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class OcsDetailsComponent implements OnInit {

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
  
  oc: any = {
    id: -1,
    faena_name: null,
    cliente_name: null,
    marca_name: null,
    proveedor_name: null,
    estadooc_id: -1,
    estadooc_name: null,
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _ocsService: OcsService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.oc.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadOC();
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
  
  private loadFormData(ocData: any)
  { 
    if(ocData['partes'].length > 0)
    {
      this.oc.id = ocData.id;
      this.oc.faena_name = ocData.cotizacion.solicitud.faena.name;
      this.oc.cliente_name = ocData.cotizacion.solicitud.faena.cliente.name;
      this.oc.marca_name = ocData.cotizacion.solicitud.marca.name;
      if(ocData.proveedor !== null)
      {
        this.oc.proveedor_name = ocData.proveedor.name;
      }
      this.oc.estadooc_id = ocData.estadooc.id;
      this.oc.estadooc_name = ocData.estadooc.name;

      this.partes = [];
      let statusDays = null
      ocData.partes.forEach((p: any) => {
        
        // Gets time diff from last pivot update to today (in ms) and convert it to days
        statusDays = Math.floor(((new Date().getTime()) - (new Date(p.pivot.updated_at).getTime())) / (1000 * 60 * 60 * 24));
        this.partes.push(
          {
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            'statusdays': statusDays,
            'estadoocparte_id': p.pivot.estadoocparte.id,
            'estadoocparte_name': p.pivot.estadoocparte.name,
            'backorder': p.pivot.backorder === 1 ? true : false,
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
      this.goTo_ocsList();
    }
  }

  public loadOC(): void {
    
    this.loading = true;

    this._ocsService.getOC(this.oc.id)
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
              'Error al cargar los datos de la solicitud',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_ocsList();
      }
    );
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public goTo_ocsList(): void {
    this.router.navigate(['/panel/ocs']);
  }

}
