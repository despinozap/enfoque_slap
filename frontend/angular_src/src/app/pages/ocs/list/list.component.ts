import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { OcsService } from 'src/app/services/ocs.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class OcsListComponent implements OnInit, AfterViewInit {

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

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
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
}
