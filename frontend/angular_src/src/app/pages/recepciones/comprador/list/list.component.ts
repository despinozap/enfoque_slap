import { Component, OnInit, ViewChild } from '@angular/core';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { NotificationsService } from 'src/app/services/notifications.service';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class RecepcionesCompradorListComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_recepciones: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    columnDefs: [
      { orderable: false, targets: 0 }
    ],
    order: [[1, 'desc']],
    /*
    // Declare the use of the extension in the dom parameter
    dom: 'Bfrtip',
    // Configure the buttons
    buttons: [
      'colvis',
      'excel',
      'pdf',
      'print'
    ]
    */
  };
  
  dtTrigger: Subject<any> = new Subject<any>();

  recepciones: any[] = [];
  comprador_id : number = 1;
  loading: boolean = false;
  
  constructor(
    private _recepcionesService: RecepcionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadRecepcionesList();
    },
    100);
  }

  ngOnDestroy(): void {
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

  public loadRecepcionesList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_recepciones);
    this._recepcionesService.getRecepciones_comprador(this.comprador_id)
    .subscribe(
      //Success request
      (response: any) => {
        
        this.recepciones = response.data;
        this.renderDataTable(this.datatableElement_recepciones);

        this.loading = false;
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
              'Error al intentar cargar la lista de recepciones',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.recepciones = null as any;
        this.loading = false;
      }
    );
  }
  
  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

}
