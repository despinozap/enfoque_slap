import { Component, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { Proveedor } from 'src/app/interfaces/proveedor';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { RecepcionesService } from 'src/app/services/recepciones.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { ProveedoresService } from 'src/app/services/proveedores.service';
import { UtilsService } from 'src/app/services/utils.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-create',
  templateUrl: './create.component.html',
  styleUrls: ['./create.component.css']
})
export class RecepcionesCompradorCreateComponent implements OnInit {

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

  proveedores: Array<Proveedor> = null as any;
  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  recepcionForm: FormGroup = new FormGroup({
    proveedor: new FormControl('', [Validators.required]),
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  comprador_id: number = 1;

  constructor(
    private location: Location,
    private router: Router,
    private _proveedoresService: ProveedoresService,
    private _recepcionesService: RecepcionesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
        this.loadProveedores();

        this.recepcionForm.controls.fecha.setValue(this.getDateToday());
      },
      100
    );
  }

  ngOnDestroy(): void {
    this.dtTrigger.unsubscribe();
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
    this.recepcionForm.disable();

    this._proveedoresService.getProveedores(this.comprador_id)
    .subscribe(
      //Success request
      (response: any) => {

        this.proveedores = response.data;
        this.loading = false;
        this.recepcionForm.enable();
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
              'Error al cargar los datos de los proveedores',
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

  public loadQueuePartes(): void {
    this.loading = true;
    this.recepcionForm.disable();

    this._recepcionesService.getQueuePartes_comprador(this.comprador_id, this.recepcionForm.value.proveedor)
    .subscribe(
      //Success request
      (response: any) => {

        this.partes = response.data.reduce((carry: any[], parte: any) => {
            carry.push({
              id: parte.id,
              nparte: parte.parte.nparte,
              descripcion: parte.descripcion,
              cantidad_pendiente: parte.cantidad_pendiente,
              backorder: parte.backorder,
              oc_id: parte.oc.id,
              checked: false,
              cantidad: parte.cantidad_pendiente,
              comentario: null
            });

            return carry;
          },
          [] // Empty array
        );

        this.renderDataTable(this.datatableElement_partes, this.dtTrigger);

        this.loading = false;
        this.recepcionForm.enable();
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
              'Error al cargar los datos de partes',
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

  public storeRecepcion(): void {

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

  public updateParte_comentario(parte: any, evt: any): void {
    parte.comentario = evt.target.value;
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

  public goTo_recepcionesList(): void {
    this.router.navigate(['/panel/recepciones/comprador']);
  }

  public goTo_back(): void {
    this.location.back();
  }
}
