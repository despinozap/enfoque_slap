import { Component, OnInit, ViewChild } from '@angular/core';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { DespachosService } from 'src/app/services/despachos.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class DespachosCompradorEditComponent implements OnInit {

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

  despacho: any = {
    id: -1,
    centrodistribucion_id: -1,
    centrodistribucion_name: null,
  };

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];

  despachoForm: FormGroup = new FormGroup({
    fecha: new FormControl('', [Validators.required, Validators.minLength(1)]),
    documento: new FormControl(''),
    responsable: new FormControl('', [Validators.required, Validators.minLength(2)]),
    comentario: new FormControl(''),
  });

  private sub: any;
  comprador_id: number = 1;
  
  
  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _despachosService: DespachosService,
    private _utilsService: UtilsService,
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.despacho.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadDespacho();
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

  private loadFormData(despachoData: any)
  {
    if(despachoData.despacho['ocpartes'].length > 0)
    {
      // Load Despacho data
      this.despacho.id = despachoData.despacho.id;
      this.despacho.centrodistribucion_id = despachoData.despacho.destinable.id;
      this.despacho.centrodistribucion_name = despachoData.despacho.destinable.name;

      this.despachoForm.controls.fecha.setValue(this.dateStringFormat(despachoData.despacho.fecha));
      if(despachoData.despacho.ndocumento !== null)
      {
        this.despachoForm.controls.documento.setValue(despachoData.despacho.ndocumento);
      }
      this.despachoForm.controls.responsable.setValue(despachoData.despacho.responsable);
      if(despachoData.despacho.comentario !== null)
      {
        this.despachoForm.controls.comentario.setValue(despachoData.despacho.comentario);
      }

      // Load partes list from queueOcPartes
      this.partes = despachoData.queue_ocpartes.map((ocparte: any) => 
        {
          return {
            id: ocparte.parte.id,
            descripcion: ocparte.descripcion,
            nparte: ocparte.parte.nparte,
            marca_name: ocparte.parte.marca.name,
            oc_id: ocparte.oc.id,
            oc_noccliente: ocparte.oc.noccliente,
            backorder: ocparte.backorder === 1 ? true : false,
            sucursal_name: ocparte.oc.cotizacion.solicitud.sucursal.name,
            faena_name: ocparte.oc.cotizacion.solicitud.faena.name,
            cantidad_min: 1,
            cantidad_stock: ocparte.cantidad_recepcionado - ocparte.cantidad_despachado,
            cantidad: ocparte.cantidad_recepcionado - ocparte.cantidad_despachado,
            checked: false
          };
        }
      );

      let index: number;

      // Update values with partes list in Despacho 
      despachoData.despacho.ocpartes.forEach((parteD: any) => {

        index = this.partes.findIndex((parteQ) => {
          return (parteD.parte.id === parteQ.id);
        });

        if(index >= 0)
        {
          // Update data for parte in Despacho
          this.partes[index].checked = true;
          this.partes[index].cantidad = parteD.pivot.cantidad;
          this.partes[index].cantidad_min = (parteD.cantidad_min > 0) ? parteD.cantidad_min : 1;
          this.partes[index].cantidad_stock += parteD.pivot.cantidad;
        }

      });

      this.renderDataTable(this.datatableElement_partes);

      this.sortPartesByChecked();
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_despachosList();
    }
  }

  public loadDespacho(): void {
    
    this.loading = true;

    this._despachosService.prepareUpdateDespacho_comprador(this.comprador_id, this.despacho.id)
    .subscribe(
      //Success request
      (response: any) => {

        this.loading = false;
        this.loadFormData(response.data);
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
              'Error al cargar los datos del despacho',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_despachosList();
      }
    );
  }

  public updateDespacho(): void {
    this.despachoForm.disable();
    this.loading = true;
    this.responseErrors = [];

    // Prepare OCs list
    let indexOc;
    let indexParte;
    let dispatchedOcs = this.partes.reduce((carry, parte) =>
      {
        if(parte.checked === true)
        {
          indexOc = carry.findIndex((oc: any) => {
            return (oc.id === parte.oc_id);
          });
  
          // If Oc already exists in list
          if(indexOc >= 0)
          {
            indexParte = carry[indexOc].partes.findIndex((p: any) => {
              return (p.id === parte.id);
            });
  
            // If Parte is already in the partes list for Oc
            if(indexParte >= 0)
            {
              // Adds cantidad to the existing parte
              carry[indexOc].partes[indexParte] += parte.cantidad;
            }
            // If Parte isn't in the partes list for Oc
            else
            {
              // Add Parte to the partes list in Oc
              carry[indexOc].partes.push(
                {
                  id: parte.id,
                  cantidad: parte.cantidad
                }
              );
            }
          }
          // If doesn't exist
          else
          {
            // Add the OC to list and also add the parte in partes list for the Oc
            carry.push(
              {
                id: parte.oc_id,
                partes: [
                  {
                    id: parte.id,
                    cantidad: parte.cantidad
                  }
                ]
              }
            );
          }

        }

        return carry;
      },
      []
    );

    let despacho: any = {
      fecha: this.despachoForm.value.fecha,
      ndocumento: this.despachoForm.value.documento,
      responsable: this.despachoForm.value.responsable,
      comentario: this.despachoForm.value.comentario,
      ocs: dispatchedOcs
    };

    this._despachosService.updateDespacho_comprador(this.comprador_id, this.despacho.id, despacho)
      .subscribe(
        //Success request
        (response: any) => {

          NotificationsService.showToast(
            response.message,
            NotificationsService.messageType.success
          );

          this.goTo_despachosList();
        },
        //Error request
        (errorResponse: any) => {

          console.log(errorResponse);
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
                  'Error al intentar actualizar el despacho',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.despachoForm.enable();
          this.loading = false;
        }
      );
  }

  public updateParte_cantidad(parte: any, evt: any): void {
    if(
        (isNaN(evt.target.value) === false) && 
        (parseInt(evt.target.value) > 0) && 
        (parseInt(evt.target.value) >= parte.cantidad_min) &&
        (parseInt(evt.target.value) <= parte.cantidad_stock)
    )
    {
        parte.cantidad = parseInt(evt.target.value);
    }
    else
    {
      evt.target.value = parte.cantidad;
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

  private sortPartesByChecked(): void {
    // Sort partes pushing checked ones to the top
    this.partes = this.partes.sort((p1, p2) => {
      return ((p2.checked === true) ? 1 : 0) - ((p1.checked === true) ? 1 : 0);
    });
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

  public goTo_despachosList(): void {
    this.router.navigate(['/panel/despachos/comprador']);
  }

}
