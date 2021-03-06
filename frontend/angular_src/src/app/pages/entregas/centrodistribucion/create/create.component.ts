import { Component, OnInit, QueryList, ViewChildren } from '@angular/core';
import { Location } from '@angular/common';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { EntregasService } from 'src/app/services/entregas.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class EntregasCentrodistribucionCreateComponent implements OnInit {

  @ViewChildren(DataTableDirective)
  datatableELements: QueryList<DataTableDirective> = null as any;
  dtOptionsOcs: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };
  dtOptionsPartes: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    columnDefs: [
      { 
        targets: [0, 3],
        orderable: false
      }
    ],
    order: [[1, 'desc']]
  };
  
  dtTriggerOcs: Subject<any> = new Subject<any>();
  dtTriggerPartes: Subject<any> = new Subject<any>();

  ocs: any[] = [];
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  entregaForm: FormGroup = new FormGroup({
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  /*
  *   Displayed form:
  * 
  *       0: OCs list
  *       1: Entrega form
  */
   DISPLAYING_FORM: number = 0;

  centrodistribucion_id: number = 1;
  oc: any = null;


  constructor(
    private location: Location,
    private router: Router,
    private _entregasService: EntregasService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTriggerOcs.next();
    this.dtTriggerPartes.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
        this.loadOcs();

        this.entregaForm.controls.fecha.setValue(this.getDateToday());
      },
      100
    );
  }

  ngOnDestroy(): void {
    this.dtTriggerOcs.unsubscribe();
    this.dtTriggerPartes.unsubscribe();
  }

  private renderDataTable(dataTableElement: DataTableDirective, trigger: Subject<any>): void {
    dataTableElement.dtInstance.then((dtInstance: DataTables.Api) => {
      // Destroy the table first
      dtInstance.destroy();
      // Call the trigger to rerender again
      trigger.next();
    });
  }
  
  public loadOcs(): void {
    
    this.loading = true;
    this.entregaForm.disable();

    this._entregasService.getQueueOcs_centrodistribucion(this.centrodistribucion_id)
    .subscribe(
      //Success request
      (response: any) => {

        if(response.data.length > 0) 
        {
          // Ocs
          this.ocs = response.data;

          // Uses the first datatables instance
          this.renderDataTable(this.datatableELements.first, this.dtTriggerOcs);

          this.loading = false;
          this.entregaForm.enable();
        }
        else
        {
          NotificationsService.showToast(
            'No se encontraron OCs con partes pendiente de entrega',
            NotificationsService.messageType.info
          );

          this.loading = false;
          this.goTo_back();
        }
        
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
              'Error al cargar los datos de las OCs',
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

  public loadData(): void {
    
    this.loading = true;
    this.entregaForm.disable();

    this._entregasService.prepareStoreEntrega_centrodistribucion(this.centrodistribucion_id, this.oc.id)
    .subscribe(
      //Success request
      (response: any) => {

        // Partes
        this.partes = response.data.queue_partes.reduce((carry: any[], parte: any) => {
          
            carry.push({
              id: parte.id,
              nparte: parte.nparte,
              marca: parte.marca,
              descripcion: parte.pivot.descripcion,
              backorder: parte.pivot.backorder > 0 ? true : false,
              cantidad: parte.pivot.cantidad_stock,
              cantidad_stock: parte.pivot.cantidad_stock,
              cantidad_pendiente: parte.pivot.cantidad - parte.pivot.cantidad_entregado,
              estadoocparte: parte.pivot.estadoocparte,
              checked: false,
            });

            return carry;
          },
          [] // Empty array
        );

        // Uses the second (and last) datatables instance
        this.renderDataTable(this.datatableELements.last, this.dtTriggerPartes);

        this.sortPartesByChecked();
        
        this.loading = false;
        this.entregaForm.enable();
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
              'Error al cargar los datos de la OC',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        // Uses the second (and last) datatables instance
        this.renderDataTable(this.datatableELements.last, this.dtTriggerPartes);
    
        this.loading = false;
        this.goTo_back();
      }
    );
  }

  public storeEntrega(): void {
    this.entregaForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let deliveredPartes = this.partes.reduce((carry, parte) => 
      {
        if(parte.checked === true)
        {
          carry.push(
            {
              id: parte.id,
              cantidad: parte.cantidad
            }
          );
        }
      
        return carry;
      }, 
      []
    );

    let entrega: any = {
      fecha: this.entregaForm.value.fecha,
      ndocumento: this.entregaForm.value.documento,
      responsable: this.entregaForm.value.responsable,
      comentario: this.entregaForm.value.comentario,
      partes: deliveredPartes
    };

    this._entregasService.storeEntrega_centrodistribucion(this.centrodistribucion_id, this.oc.id, entrega)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_entregasList();
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
                  'Error al intentar guardar la entrega',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.entregaForm.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0) && (parseInt(evt.target.value) <= parte.cantidad_stock))
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_stock;
    }
  }

  public checkPartesList(evt: any): void {
    this.partes.forEach((parte: any) => {
      parte.checked = evt.target.checked;
    });
    
    this.sortPartesByChecked();
  }

  public checkParteItem(parte: any, evt: any): void {
    parte.checked = evt.target.checked;
    
    this.sortPartesByChecked();
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

  private sortPartesByChecked(): void {
    // Sort partes pushing checked ones to the top
    this.partes = this.partes.sort((p1, p2) => {
      return ((p2.checked === true) ? 1 : 0) - ((p1.checked === true) ? 1 : 0);
    });
  }
  
  public getDateToday(): string {
    let dt = new Date();

    return this.dateStringFormat(`${dt.getFullYear()}-${(dt.getMonth() + 1)}-${dt.getDate()}`);
  }

  private dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public goTo_entregaForm(oc: any): void {
    this.oc = oc;
    this.loadData();

    this.DISPLAYING_FORM = 1;
  }

  public goTo_ocsList(): void {
    this.oc = null;
    this.entregaForm.reset();
    this.DISPLAYING_FORM = 0;
  }

  public goTo_entregasList(): void {
    this.router.navigate(['/panel/entregas/centrodistribucion']);
  }

  public goTo_back(): void {
    this.location.back();
  }

}
