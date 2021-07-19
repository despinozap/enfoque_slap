import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { OcsService } from 'src/app/services/ocs.service';
import { UtilsService } from 'src/app/services/utils.service';
import { PDFOcComponent } from '../../pdfs/oc/oc.component';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');


@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class OcsListComponent implements OnInit, AfterViewInit {

  @ViewChild('reportOc') reportOc: PDFOcComponent = null as any;
  @ViewChild(DataTableDirective, {static: false})
  datatableElement_ocs: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
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

  loggedUser: User = null as any;
  private subLoggedUser: any;
  
  ocs: any[] = [];
  loading: boolean = false;

  reportsDataOc: any[] = [];
  
  constructor(
    private _authService: AuthService,
    private _ocsService: OcsService,
    private _utilsService: UtilsService
  ) {
  }

  ngOnInit(): void {
    //For loggedUser
    {
      this.subLoggedUser = this._authService.loggedUser$.subscribe((data) => {
        this.loggedUser = data.user as User;
      });
      
      this._authService.notifyLoggedUser(this._authService.NOTIFICATION_RECEIVER_CONTENTPAGE);
    }
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadOcsList();
    },
    100);
  }

  ngOnDestroy(): void {
    this.subLoggedUser.unsubscribe();
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

  public loadOcsList()
  {
    this.loading = true;

    this.clearDataTable(this.datatableElement_ocs);
    this._ocsService.getOCs()
    .subscribe(
      //Success request
      (response: any) => {
        
        this.ocs = response.data;
        this.ocs.forEach((oc: any) => {
          oc['checked'] = false;
        });

        this.renderDataTable(this.datatableElement_ocs);

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
              'Error al intentar cargar la lista de ocs',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.ocs = null as any;
        this.loading = false;
      }
    );
  }

  private showReportStatus(status: string)
  {
    let el = document.getElementById('swal2-content');
    if(el !== null)
    {
      el.innerHTML = `${status} ..`;
    }
  }

  public loadReportsOc(ids: any)
  {
    Swal.queue([{
      title: 'Generar PDF',
      imageUrl: 'assets/images/icons/pdf.png',
      imageWidth: 88,
      imageHeight: 88,
      text: 'Solicitando información de reportabilidad ..',
      showConfirmButton: false,
      showCancelButton: false,
      allowOutsideClick: false,
      showLoaderOnConfirm: true,
      preConfirm: () => {
      
      }    
    }]);

    this.loading = true;

    let data = {
      ocs: ids
    };

    this._ocsService.getReportOc(data)
    .subscribe(
      //Success request
      (response: any) => {
        this.showReportStatus('Información de reportabilidad obtenida');
        
        // Load reports data
        this.parseReportsDataOc(response.data);
        this.loading = false;
      },
      //Error request
      (errorResponse: any) => {

        switch(errorResponse.status)
        {     
          case 405: //Permission denied
          {
            Swal.close();

            NotificationsService.showAlert(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }

          case 500: //Internal server
          {
            Swal.close();

            NotificationsService.showAlert(
              errorResponse.error.message,
              NotificationsService.messageType.error
            );

            break;
          }
        
          default: //Unhandled error
          {
            Swal.close();

            NotificationsService.showAlert(
              'Error al intentar cargar la lista de reportes',
              NotificationsService.messageType.error
            )
        
            break;
          }
        }

        this.reportsDataOc = [];
        this.loading = false;
      }
    );
  }
  
  private parseReportsDataOc(reportsData: any)
  { 
    this.showReportStatus('Analizando información de reportabilidad');

    this.reportsDataOc = [];
    let oc: any;

    reportsData.forEach((ocData: any) => {
      oc = {
        id: -1,
        currentdate: null,
        created_at: null,
        comprador_name: null,
        comprador_address: null,
        comprador_city: null,
        comprador_email: null,
        comprador_phone: null,
        supplier_name: null,
        supplier_address: null,
        supplier_city: null,
        supplier_email: null,
        supplier_phone: null,
        delivered: false,
        delivery_name: null,
        delivery_address: null,
        delivery_city: null,
        delivery_email: null,
        delivery_phone: null,
        //Partes
        partes: []
      };

      if(ocData['partes'].length > 0)
      {
        let today = new Date();
        oc.currentdate = `${today.getFullYear()}-${(today.getMonth() + 1) < 10 ? '0' + (today.getMonth() + 1) : (today.getMonth() + 1)}-${today.getDate() < 10 ? '0' + today.getDate() : today.getDate()}`;
        oc.id = ocData.id,
        oc.created_at = ocData.created_at;
        oc.comprador_name = ocData.cotizacion.solicitud.comprador.name;
        oc.comprador_address = ocData.cotizacion.solicitud.comprador.address;
        oc.comprador_city = ocData.cotizacion.solicitud.comprador.city;
        oc.comprador_email = ocData.cotizacion.solicitud.comprador.email;
        oc.comprador_phone = ocData.cotizacion.solicitud.comprador.phone;
        oc.supplier_name = ocData.proveedor.name;
        oc.supplier_address = ocData.proveedor.address;
        oc.supplier_city = ocData.proveedor.city;
        oc.supplier_email = ocData.proveedor.email;
        oc.supplier_phone = ocData.proveedor.phone;
        // If OC's proveedor is delivered
        if(ocData.proveedor.delivered === 1)
        {
          oc.delivered = true;
          oc.delivery_name = ocData.proveedor.delivery_name;
          oc.delivery_address = ocData.proveedor.delivery_address;
          oc.delivery_city = ocData.proveedor.delivery_city;
          oc.delivery_email = ocData.proveedor.delivery_email;
          oc.delivery_phone = ocData.proveedor.delivery_phone;
        }
        else
        {
          oc.delivered = false;
          oc.delivery_name = null;
          oc.delivery_address = null;
          oc.delivery_city = null;
          oc.delivery_email = null;
          oc.delivery_phone = null;
        }

        oc.checked = false;

        oc.partes = ocData.partes.map((p: any) => 
          {
            return {
              'id': p.id,
              'nparte': p.nparte,
              'descripcion': p.pivot.descripcion,
              'cantidad': p.pivot.cantidad,
              'costo': p.pivot.costo
            };
          }
        );

        this.reportsDataOc.push(oc);
      }
      else
      {
        Swal.close();

        NotificationsService.showToast(
          'Error al intentar cargar la lista de partes de la OC',
          NotificationsService.messageType.error
        );

        this.reportsDataOc = [];
        this.loading = false;
      }
    });

    // Generate reports delayed by 3 secs each one
    this.reportsDataOc.forEach((oc, index, arr) => {
        setTimeout(() => {
          this.showReportStatus(`Generando reporte de OC ·${oc.id}`);

          this.generateReportOcPDF(oc, (index === arr.length -1) ? true : false);
        },
        3000 * index
      );
    });
  }

  public generateReportOcPDF(oc: any, last: boolean): void {  

    // Go to top of page for report rendering
    window.scroll(0,0);
    
    // If report component was found
    if(this.reportOc !== undefined)
    {
      this.showReportStatus(`Construyendo reporte de OC #${oc.id}`);

      let reportData = {
        oc: oc,
        partes: oc.partes
      };

      // Set report data
      this.reportOc.reportData = reportData;

      // Export report to PDF after 1 sec after data loaded in report
      setTimeout(() => {
          this.showReportStatus(`Descargando reporte de OC #${oc.id}`);

          this.reportOc.exportOcToPdf();

          // If it's the last report, then close notification
          if(last === true)
          {
            setTimeout(() => {
                Swal.close();
                // Render OC list again
                this.renderDataTable(this.datatableElement_ocs);
              },
              1000
            );
          }

        },
        1000
      );
      
    }
    else
    {
      Swal.close();

      NotificationsService.showToast(
        'Error al generar el reporte de OC',
        NotificationsService.messageType.error
      );
    }
  }
  
  public exportOcsToPDF(): void {
    
    // Get selected elements id
    let ids = this.ocs.reduce(
      (idsList, oc) => {
        if(oc.checked === true)
        {
          idsList.push(oc.id);
        }

        return idsList;
      },
      [] // Initial ids list
    );

    // Request reports list
    this.loadReportsOc(ids);
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public checkOcItem(oc: any, evt: any): void {
    if([2, 3].includes(oc.estadooc.id))
    {
      oc.checked = evt.target.checked;
    }
    else
    {
      NotificationsService.showAlert(
        'Solo puedes generar reporte PDF para OC "En proceso" o "Cerrada"',
        NotificationsService.messageType.warning
      );
    }
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

}
