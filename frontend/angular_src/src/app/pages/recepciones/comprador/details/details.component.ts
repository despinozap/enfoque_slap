import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Location } from '@angular/common';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { UtilsService } from 'src/app/services/utils.service';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';

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
    proveedor_name: null,
    fecha: null,
    ndocumento: null,
    responsable: null,
    comentario: null,
    created_at: null    
  };
  oc: any = {
    id: -1,
    noccliente: null,
    cliente_name: null,
    faena_name: null
  }
  
  private sub: any;
  
  loggedUser: User = null as any;
  private subLoggedUser: any;
  
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  constructor(
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _recepcionesService: RecepcionesService,
    private location: Location,
    private _utilsService: UtilsService,
  ) { }

  ngOnInit(): void {
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user as User;
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }

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
    this.subLoggedUser.unsubscribe();
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
    if(recepcionData.recepcion.ocpartes.length > 0)
    {
      this.recepcion.id = recepcionData.recepcion.id;
      this.recepcion.proveedor_name = recepcionData.recepcion.sourceable.name;
      this.recepcion.fecha = recepcionData.recepcion.fecha;
      this.recepcion.ndocumento = recepcionData.recepcion.ndocumento;
      this.recepcion.responsable = recepcionData.recepcion.responsable;
      this.recepcion.comentario = recepcionData.recepcion.comentario;
      this.recepcion.created_at = recepcionData.recepcion.created_at;
      
      this.oc.id = recepcionData.oc.id;
      this.oc.noccliente = recepcionData.oc.noccliente;
      this.oc.cliente_name = recepcionData.oc.cotizacion.solicitud.faena.cliente.name;
      this.oc.faena_name = recepcionData.oc.cotizacion.solicitud.faena.name;
      
      this.partes = [];
      recepcionData.recepcion.ocpartes.forEach((ocParte: any) => {
        this.partes.push(
          {
            'id': ocParte.parte.id,
            'nparte': ocParte.parte.nparte,
            'descripcion': ocParte.descripcion,
            'marca_name': ocParte.parte.marca.name,
            'cantidad': ocParte.pivot.cantidad,
            'backorder': ocParte.backorder === 1 ? true : false
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
