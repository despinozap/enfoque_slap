import { Component, OnInit, ViewChild } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { CompradoresService } from 'src/app/services/compradores.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ProveedoresService } from 'src/app/services/proveedores.service';

/* SweetAlert2 */
const Swal = require('../../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');


@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ProveedoresListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_proveedores: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };
  
  dtTrigger: Subject<any> = new Subject<any>();
  
  comprador: any = {
    id: null,
    name: null,
  };

  proveedores: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _compradoresService: CompradoresService,
    private _proveedoresService: ProveedoresService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.comprador.id = params['comprador_id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadComprador();
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

  private loadFormData(compradorData: any)
  {
    this.comprador.name = compradorData.name;
    this.proveedores = compradorData.proveedores;
  }

  public loadComprador(): void {
    
    this.loading = true;

    this._compradoresService.getComprador(this.comprador.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;
        this.loadFormData(response.data);

        this.renderDataTable(this.datatableElement_proveedores);
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
              'Error al cargar los datos del comprador',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_compradoresList();
      }
    );
  }

  public removeProveedor(proveedor: any)
  {
    Swal.fire({
      title: 'Eliminar proveedor',
      text: `¿Realmente quieres eliminar el proveedor "${ proveedor.name }"?`,
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
        Swal.queue([{
          title: 'Eliminando..',
          icon: 'warning',
          showConfirmButton: false,
          showCancelButton: false,
          allowOutsideClick: false,
          showLoaderOnConfirm: true,
          preConfirm: () => {

          }    
        }]);

        this._proveedoresService.removeProveedor(this.comprador.id, proveedor.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadComprador();

            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

          },
          //Error request
          (errorResponse: any) => {

            switch(errorResponse.status)
            {
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
                  NotificationsService.messageType.warning
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
                  'Error al intentar eliminar el proveedor',
                  NotificationsService.messageType.error
                );

                break;
              }
            }
          }
        );
      }
    });

  }  

  public goTo_newProveedor(): void {
    this.router.navigate([`/panel/compradores/${this.comprador.id}/proveedores/create`]);
  }

  public goTo_compradoresList(): void {
    this.router.navigate(['/panel/compradores']);
  }

}
