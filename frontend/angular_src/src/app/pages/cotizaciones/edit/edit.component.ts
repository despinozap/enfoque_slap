import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { CotizacionesService } from 'src/app/services/cotizaciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class CotizacionesEditComponent implements OnInit {

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
  
  loggedUser: User = null as any;
  private subLoggedUser: any;

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;
  
  cotizacion: any = {
    id: -1,
    dias: -1,
    cliente_name: null,
    sucursal_name: null,
    faena_name: null,
    marca_name: null,
    comprador_name: null,
    estadocotizacion_id: -1,
    estadocotizacion_name: null,
    created_at: null,
  };

  daysDiff: number = -1;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _cotizacionesService: CotizacionesService,
    private _utilsService: UtilsService
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
      this.cotizacion.id = params['id'];
    });

  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadCotizacion();
    },
    100);
  }

  ngOnDestroy(): void {
    this.subLoggedUser.unsubscribe();
    this.sub.unsubscribe();
    this.dtTrigger.unsubscribe();
  }

  private clearDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Clear the table first
      dtInstance.clear();
    });
  }

  private renderDataTable(dataTableElement: DataTableDirective): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the dtTrigger to rerender again
      this.dtTrigger.next();
    });
  }

  private loadFormData(cotizacionData: any)
  { 
    if(cotizacionData['partes'].length > 0)
    {  
      // Get days diff since last values update until now
      this.daysDiff = (Date.now() - Date.parse(cotizacionData.lastupdate))/(1000 * (60 * 60) * 24);

      // Common fields
      this.cotizacion.id = cotizacionData.id;
      this.cotizacion.dias = cotizacionData.dias;
      this.cotizacion.cliente_name = cotizacionData.solicitud.faena.cliente.name;
      this.cotizacion.sucursal_name = cotizacionData.solicitud.sucursal.name;
      this.cotizacion.faena_name = cotizacionData.solicitud.faena.name;
      this.cotizacion.marca_name = cotizacionData.solicitud.marca.name;
      this.cotizacion.comprador_name = cotizacionData.solicitud.comprador.name;
      this.cotizacion.estadocotizacion_id = cotizacionData.estadocotizacion.id,
      this.cotizacion.estadocotizacion_name = cotizacionData.estadocotizacion.name;
      this.cotizacion.created_at = cotizacionData.created_at;

      this.partes = [];
      cotizacionData.partes.forEach((p: any) => {
        this.partes.push(
          {
            'id': p.id,
            'nparte': p.nparte,
            'descripcion': p.pivot.descripcion,
            'cantidad': p.pivot.cantidad,
            'costo': p.pivot.costo,
            'margen': p.pivot.margen,
            'tiempoentrega': p.pivot.tiempoentrega,
            'peso': p.pivot.peso,
            'flete': p.pivot.flete,
            'monto': p.pivot.monto,
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
      this.goTo_cotizacionesList();
    }
  }

  public loadCotizacion(): void {
    
    this.clearDataTable(this.datatableElement_partes);

    this.loading = true;
    let data = {
      cotizaciones: [this.cotizacion.id]
    };

    this._cotizacionesService.getReportCotizacion(data)
    .subscribe(
      //Success request
      (response: any) => {
        // Loads the first item
        if(response.data.length > 0)
        {
          this.loadFormData(response.data[0]);
          this.renderDataTable(this.datatableElement_partes);

          this.loading = false;
        }
        else
        {
          NotificationsService.showToast(
            'Error al cargar los datos de la cotizacion',
            NotificationsService.messageType.error
          );

          this.loading = false;
          this.goTo_cotizacionesList();
        }
        
      },
      //Error request
      (errorResponse: any) => {
        
        switch(errorResponse.status)
        {
        
          case 400: //Bad request
          {
            NotificationsService.showToast(
              'Error al cargar los datos de la cotizacion',
              NotificationsService.messageType.error
            );

            break;
          }

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
              'Error al cargar los datos de la cotizacion',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_cotizacionesList();
      }
    );
  }

  public updateCotizacion()
  {
    this.loading = true;
    this.responseErrors = [];

    let cotizacion: any = {
      partes: this.partes
    };
    
    this._cotizacionesService.updateCotizacion(this.cotizacion.id, cotizacion)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_cotizacionesList();
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
                  'Error al intentar guardar la cotizacion',
                  NotificationsService.messageType.error
                );

                break;
              }
          }
          
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0))
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_stock;
    }
  }

  // Modified for removing decimals when in CLP value
  public moneyStringFormat(value: number): string {
    let moneyStr = this._utilsService.moneyStringFormat(value);
    
    // If role is Seller
    if((this.loggedUser !== null) && (this.loggedUser.role.name === 'seller'))
    {
      // Modify value removing decimals
      let index = moneyStr.indexOf('.');
      moneyStr = moneyStr.substring(0, index);
    }
    
    return moneyStr;
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public removeParte(index: number): void
  {
    if(this.partes.length < 2)
    {
      NotificationsService.showAlert(
        'La cotizacion debe tener al menos 1 parte en la lista',
        NotificationsService.messageType.warning
      );
    }
    else
    {
      Swal.fire({
        title: 'Eliminar parte',
        text: "Una vez eliminada la parte no es posible volver a agregarla. ¿Realmente deseas eliminar la parte?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#555555',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false
      }).then((result: any) => {
        if(result.isConfirmed)
        {
          this.partes.splice(index, 1);
          this.renderDataTable(this.datatableElement_partes);
        }
      });
    }

  }

  public goTo_cotizacionesList(): void {
    this.router.navigate(['/panel/cotizaciones']);
  }

}
