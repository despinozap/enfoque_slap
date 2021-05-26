import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ClientesService } from 'src/app/services/clientes.service';
import { FaenasService } from 'src/app/services/faenas.service';

/* SweetAlert2 */
const Swal = require('../../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class FaenasListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_faenas: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };
  
  dtTrigger: Subject<any> = new Subject<any>();
  
  cliente: any = {
    id: null,
    name: null,
  };

  faenas: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  private sub: any;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _clientesService: ClientesService,
    private _faenasService: FaenasService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.cliente.id = params['cliente_id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadCliente();
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

  private loadFormData(clienteData: any)
  {
    this.cliente.name = clienteData.name;
    this.faenas = clienteData.faenas;
  }

  public loadCliente(): void {
    
    this.loading = true;

    this._clientesService.getCliente(this.cliente.id)
    .subscribe(
      //Success request
      (response: any) => {
        this.loading = false;
        this.loadFormData(response.data);

        this.renderDataTable(this.datatableElement_faenas);
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {
        
          case 400: ////Invalid request parameters
          {
            NotificationsService.showToast(
              errorResponse.error.message,
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
              'Error al cargar los datos del cliente',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_clientesList();
      }
    );
  }

  public removeFaena(faena: any)
  {
    Swal.fire({
      title: 'Eliminar faena',
      text: `¿Realmente quieres eliminar la faena "${ faena.name }"?`,
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

        this._faenasService.removeFaena(this.cliente.id, faena.id)
        .subscribe(
          //Success request
          (response: any) => {

            this.loadCliente();

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
                  'Error al intentar eliminar la faena',
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

  public goTo_newFaena(): void {
    this.router.navigate([`/panel/clientes/${this.cliente.id}/faenas/create`]);
  }

  public goTo_clientesList(): void {
    this.router.navigate(['/panel/clientes']);
  }

}
