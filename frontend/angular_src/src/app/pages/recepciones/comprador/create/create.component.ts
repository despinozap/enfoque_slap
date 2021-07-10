import { Component, OnInit, QueryList, ViewChildren } from '@angular/core';
import { Location } from '@angular/common';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class RecepcionesCompradorCreateComponent implements OnInit {
  @ViewChildren(DataTableDirective)
  datatableELements: QueryList<DataTableDirective> = null as any;
  dtOptionsOcs: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };
  dtOptionsPartes: any = {
    pagingType: 'full_numbers',
    pageLength: 10,
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
  
  proveedores: any[] = [];
  ocs: any[] = [];
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  proveedorForm: FormGroup = new FormGroup({
    proveedor: new FormControl('', [Validators.required]),
  });

  recepcionForm: FormGroup = new FormGroup({
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

  comprador_id: number = 1;
  oc: any = null;


  constructor(
    private location: Location,
    private router: Router,
    private _recepcionesService: RecepcionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTriggerOcs.next();
    this.dtTriggerPartes.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
        this.loadProveedores();
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

  public loadProveedores(): void {
    
    this.loading = true;
    this.proveedorForm.disable();

    this._recepcionesService.prepareStoreRecepcion_comprador(this.comprador_id)
    .subscribe(
      //Success request
      (response: any) => {

        if(response.data.proveedores.length > 0) 
        {
          // Proveedores
          this.proveedores = response.data.proveedores;

          this.loading = false;
          this.proveedorForm.enable();
        }
        else
        {
          NotificationsService.showToast(
            'No se encontraron proveedores con partes pendiente de recepcion',
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
              'Error al cargar los datos de los centros de distribucion',
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
  
  public loadOcs(): void {
    
    this.loading = true;
    this.proveedorForm.disable();

    this._recepcionesService.getQueueOcs_comprador(this.comprador_id, this.proveedorForm.value.proveedor)
    .subscribe(
      //Success request
      (response: any) => {

        // Ocs
        this.ocs = response.data;
        // Uses the first datatables instance
        this.renderDataTable(this.datatableELements.first, this.dtTriggerOcs);

        this.loading = false;
        this.proveedorForm.enable();
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
    this.recepcionForm.disable();

    // Partes
    this.partes = this.oc.partes.reduce((carry: any[], parte: any) => {

        if((parte.pivot.cantidad - parte.pivot.cantidad_recepcionado) > 0)
        {
          carry.push({
            id: parte.id,
            nparte: parte.nparte,
            descripcion: parte.pivot.descripcion,
            marca_name: parte.marca.name,
            backorder: parte.backorder > 0 ? true : false,
            cantidad: parte.pivot.cantidad - parte.pivot.cantidad_recepcionado,
            cantidad_total: parte.pivot.cantidad,
            cantidad_recepcionado: parte.pivot.cantidad_recepcionado,
            cantidad_pendiente: parte.pivot.cantidad - parte.pivot.cantidad_recepcionado,
            checked: false
          });
        }
      
        return carry;
      },
      [] // Empty array
    );

    // Uses the second (and last) datatables instance
    this.renderDataTable(this.datatableELements.last, this.dtTriggerPartes);

    this.loading = false;
    this.recepcionForm.enable();
  }

  public storeRecepcion(): void {
    this.recepcionForm.disable();
    this.loading = true;
    this.responseErrors = [];

    let receivedOcs = this.partes.reduce((carry: any[], parte: any) => 
      {
        if(parte.checked === true)
        {
          // Add parte to the only one OC in carry
          carry[0].partes.push(
            {
              id: parte.id,
              cantidad: parte.cantidad
            }
          );
        }
      
        return carry;
      },
      // Initialize array including an only one OC 
      [
        {
          id: this.oc.id,
          partes: []
        }
      ]
    );

    let recepcion: any = {
      proveedor_id: this.proveedorForm.value.proveedor,
      fecha: this.recepcionForm.value.fecha,
      ndocumento: this.recepcionForm.value.documento,
      responsable: this.recepcionForm.value.responsable,
      comentario: this.recepcionForm.value.comentario,
      ocs: receivedOcs
    };

    this._recepcionesService.storeRecepcion_comprador(this.comprador_id, recepcion)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_recepcionesList();
        },
        //Error request
        (errorResponse: any) => {

          switch (errorResponse.status) 
          {
            case 400: //Invalid request parameters
              {
                if(
                    (errorResponse.error.message.proveedor_id !== undefined) && 
                    (errorResponse.error.message.proveedor_id.length > 0)
                )
                {
                  NotificationsService.showAlert(
                    errorResponse.error.message.proveedor_id[0],
                    NotificationsService.messageType.error
                  );
                }
                else
                {
                  this.responseErrors = errorResponse.error.message;
                }

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
                  'Error al intentar guardar la recepcion',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.recepcionForm.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if((isNaN(evt.target.value) === false) && (parseInt(evt.target.value) > 0) && (parseInt(evt.target.value) <= parte.cantidad_pendiente))
    {
      parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad_pendiente;
    }
  }

  public checkPartesList(evt: any): void {
    this.partes.forEach((parte: any) => {
      parte.checked = evt.target.checked;
    });
  }

  public checkParteItem(parte: any, evt: any): void {
    parte.checked = evt.target.checked;
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
  
  public getDateToday(): string {
    let dt = new Date();

    return this.dateStringFormat(`${dt.getFullYear()}-${(dt.getMonth() + 1)}-${dt.getDate()}`);
  }

  private dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

  public goTo_recepcionForm(oc: any): void {
    this.oc = oc;
    this.loadData();
    this.recepcionForm.controls.fecha.setValue(this.getDateToday());

    this.DISPLAYING_FORM = 1;
  }

  public goTo_ocsList(): void {
    this.oc = null;
    this.recepcionForm.reset();
    this.DISPLAYING_FORM = 0;
  }

  public goTo_recepcionesList(): void {
    this.router.navigate(['/panel/recepciones/comprador']);
  }

  public goTo_back(): void {
    this.location.back();
  }
}
