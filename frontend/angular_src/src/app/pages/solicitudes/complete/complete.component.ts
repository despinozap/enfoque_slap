import { Component, OnInit, ViewChild } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DataTableDirective } from 'angular-datatables';
import { Subject } from 'rxjs';
import { User } from 'src/app/interfaces/user';
import { AuthService } from 'src/app/services/auth.service';
import { NotificationsService } from 'src/app/services/notifications.service';
import { SolicitudesService } from 'src/app/services/solicitudes.service';
import { UtilsService } from 'src/app/services/utils.service';

/* SweetAlert2 */
const Swal = require('../../../../assets/vendors/sweetalert2/sweetalert2.all.min.js');

//XLSX lib
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-complete',
  templateUrl: './complete.component.html',
  styleUrls: ['./complete.component.css']
})
export class SolicitudesCompleteComponent implements OnInit {

  @ViewChild(DataTableDirective, {static: false})
  datatableElement_partes: DataTableDirective = null as any;
  dtOptions: any = {
    pagingType: 'full_numbers',
    pageLength: 25,
    language: {
      url: '//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json'
    },
    order: [[0, 'desc']]
  };

  dtTrigger: Subject<any> = new Subject<any>();
  
  loggedUser: User = null as any;
  private subLoggedUser: any;
  
  solicitud: any = {
    id: null,
    sucursal_name: null,
    faena_name: null,
    cliente_name: null,
    marca_name: null,
    user_name: null,
    estadosolicitud_id: -1,
    estadosolicitud_name: null,
    comentario: null
  };
  lbInUsd: number = -1;

  partes: any[] = [];
  loading: boolean = false;
  responseErrors: any = [];
  dataUpdated: boolean = false;

  private sub: any;
  private parte_index: number = -1;

  /*
  *   Displayed form:
  * 
  *       0: Solicitud
  *       1: Parte
  */
  DISPLAYING_FORM: number = 0;  

  parteForm: FormGroup = new FormGroup({
    nparte: new FormControl(''),
    cantidad: new FormControl('', [Validators.required, Validators.min(1)]),
    costo: new FormControl('', [Validators.min(0)]),
    margen: new FormControl('', [Validators.min(0)]),
    tiempoentrega: new FormControl('', [Validators.min(0)]),
    peso: new FormControl('', [Validators.min(0)]),
    flete: new FormControl('', [Validators.min(0)]),
    monto: new FormControl('', [Validators.min(0)]),
    backorder: new FormControl(''),
    descripcion: new FormControl(''),
  });

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _authService: AuthService,
    private _solicitudesService: SolicitudesService,
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
      this.solicitud.id = params['id'];
    });
  }

  ngAfterViewInit(): void {
    this.dtTrigger.next();

    //Prevents throwing an error for var status changed while initialization
    setTimeout(() => {
      this.loadSolicitud(0);
    },
    100);
  }

  ngOnDestroy() {
    this.subLoggedUser.unsubscribe();
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

  private loadFormData(solicitudData: any)
  {
    if(solicitudData.solicitud['partes'].length > 0)
    {
      // Update LB in USD value
      this.lbInUsd = solicitudData.lb_in_usd;

      if((solicitudData.solicitud.estadosolicitud.id === 1) || (solicitudData.solicitud.estadosolicitud.id === 2)) // If is 'Pendiente' or 'Completa'
      {
        this.solicitud.id = solicitudData.solicitud.id;
        this.solicitud.sucursal_name = solicitudData.solicitud.sucursal.name;
        this.solicitud.faena_name = solicitudData.solicitud.faena.name;
        this.solicitud.cliente_name = solicitudData.solicitud.faena.cliente.name;
        this.solicitud.marca_name = solicitudData.solicitud.marca.name;
        this.solicitud.user_name = solicitudData.solicitud.user.name;
        this.solicitud.estadosolicitud_id = solicitudData.solicitud.estadosolicitud.id,
        this.solicitud.estadosolicitud_name = solicitudData.solicitud.estadosolicitud.name;
        this.solicitud.comentario = solicitudData.solicitud.comentario;

        this.partes = [];
        solicitudData.solicitud.partes.forEach((p: any) => {
          this.partes.push(
            {
              'nparte': p.nparte,
              'cantidad': p.pivot.cantidad,
              'costo': p.pivot.costo,
              'margen': p.pivot.margen,
              'tiempoentrega': p.pivot.tiempoentrega,
              'peso': p.pivot.peso,
              'flete': p.pivot.flete,
              'monto': p.pivot.monto,
              'backorder': p.pivot.backorder === 1 ? true : false,
              'descripcion': p.pivot.descripcion
            }
          )
        });

        for(let i = 0; i < this.partes.length; i++)
        {
          this.partes[i].complete = this.isParteCompleted(i);
        }
      } 
      else
      {
        NotificationsService.showToast(
          `No puedes completar una solicitud con estado ${ solicitudData.solicitud.estadosolicitud.name }`,
          NotificationsService.messageType.error
        );
  
        this.loading = false;
        this.goTo_solicitudesList();
      }
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );

      this.loading = false;
      this.goTo_solicitudesList();
    }
  }

  public onPartesFileChange(evt: any): void {
    //Catches the event
    const target: DataTransfer = <DataTransfer>(evt.target);
    // Allowed extensions. All in lowercase
    let exts: string[] = [
      "xls",
      "xlsx"
    ];

    let validationMessage = this._utilsService.validateInputFile(target, exts);

    // If valid filetype
    if (validationMessage.length === 0) {
      //Prepare reader
      const reader: FileReader = new FileReader();

      //When loading file
      reader.onload = (e: any) => {

        const bstr: string = e.target.result;

        //Read the readed file as binary sheet
        const wb: XLSX.WorkBook = XLSX.read(bstr, { type: 'binary' });
        //Get the first sheet's name
        const wsname: string = wb.SheetNames[0];
        //Get the first sheet (by name)
        const ws: XLSX.WorkSheet = wb.Sheets[wsname];

        // Dumps the whole sheet into a JSON matrix
        let sheet: any[][] = (XLSX.utils.sheet_to_json(ws, { header: 1 }));

        if (sheet.length > 1) 
        {
          let parte;
          let index;
          for (let i = 1; i < sheet.length; i++) 
          {
            let response = this.validateExcelRowAsParte(sheet[i]);

            switch(response.code)
            {
              case -2: {

                //console.log(response.message);

                break;
              }

              case -1: {

                NotificationsService.showAlert(
                  response.message,
                  NotificationsService.messageType.error
                );

                break;
              }

              case 0: {

                NotificationsService.showAlert(
                  response.message,
                  NotificationsService.messageType.warning
                );

                break;
              }

              case 1: {

                index = this.partes.findIndex((p) => {
                  if(p.nparte.toUpperCase() === sheet[i][1].toUpperCase())
                  {
                    return true;
                  }
                  else
                  {
                    return false;
                  }
                });
  
                parte = this.partes[index];
                parte.cantidad = sheet[i][0] !== undefined ? sheet[i][0] : null;
                parte.descripcion = sheet[i][2] !== undefined ? sheet[i][2] : null;
                parte.costo = sheet[i][3] !== undefined ? sheet[i][3] : null;
                parte.margen = sheet[i][4] !== undefined ? sheet[i][4] : null;
                parte.tiempoentrega = sheet[i][5] !== undefined ? sheet[i][5] : null;
                parte.peso = sheet[i][6] !== undefined ? sheet[i][6] : null;
                parte.flete = this.calculateParteFlete(parte);
                parte.monto = this.calculateParteMonto(parte);
                parte.backorder = (sheet[i][7] !== undefined) && (sheet[i][7] === 1) ? true : false;
                parte.complete = this.isParteCompleted(index);
                this.dataUpdated= true;

                break;
              }

              default: {

                break;
              }
            }

          }

          this.renderDataTable(this.datatableElement_partes);
        }
        else {
          NotificationsService.showAlert(
            'El archivo cargado no contiene informacion valida',
            NotificationsService.messageType.error
          );
        }

      };

      reader.readAsBinaryString(target.files[0]);
    }
    else {
      NotificationsService.showAlert(
        validationMessage,
        NotificationsService.messageType.warning
      );
    }

  }

  public loadSolicitud(afterExecutedAction: number): void {
    /*
     *  AfterExecutionAction:
     *    0: Nothing
     *    1: Export to Excel 
     */
    this.loading = true;

    this._solicitudesService.prepareCompleteSolicitud(this.solicitud.id)
    .subscribe(
      //Success request
      (response: any) => {

        switch(afterExecutedAction){
          
          case 0: { // Nothing

            if(response.data.solicitud.estadosolicitud.id === 3) // Cerrada
            {
              NotificationsService.showToast(
                'No se puede completar una solicitud cerrada',
                NotificationsService.messageType.warning
              );

              this.loading = false;
              this.goTo_solicitudesList();
            }
            else
            {
              this.loading = false;
              this.loadFormData(response.data);

              this.renderDataTable(this.datatableElement_partes);
            }
            
            break;
          }

          case 1: { // Export partes list to Excel

            if(response.data.solicitud.estadosolicitud.id === 2) // Completa on saving before Export to Excel
            {
              this.loading = false;
              this.loadFormData(response.data);

              this.renderDataTable(this.datatableElement_partes);

              this.dataUpdated = false;

              this.exportPartesToExcel();
              
              NotificationsService.showToast(
                'Solicitud completa',
                NotificationsService.messageType.success
              );

              this.goTo_solicitudesList();
            }
            else
            {
              this.loading = false;
              this.loadFormData(response.data);

              this.renderDataTable(this.datatableElement_partes);

              this.dataUpdated = false;

              this.exportPartesToExcel();
            }

            break;
          }

          default: {

            break;
          }

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
              'Error al cargar los datos de la solicitud',
              NotificationsService.messageType.error
            );
  
            break;

          }
        }

        this.loading = false;
        this.goTo_solicitudesList();
      }
    );
  }

  public completeSolicitud(afterExecutedAction: number)
  {
    this.loading = true;
    this.responseErrors = [];

    let solicitud: any = {
      partes: this.partes
    };
    
    this._solicitudesService.completeSolicitud(this.solicitud.id, solicitud)
      .subscribe(
        //Success request
        (response: any) => {

          this.dataUpdated = false;

          switch(afterExecutedAction)
          {
          
            case 0: { // Go back to the list

              this.loading = false;

              // If Solicitud is now completed
              if(response.data.solicitud.estadosolicitud.id === 2) // Estadosolicitud = 'Completa'
              {
                // Load solicitud data and stay in page for closing
                this.loadFormData(response.data);
                this.renderDataTable(this.datatableElement_partes);
                
                // Ask for closing solicitud right away
                let question = 'Se ha completado la solicitud. ¿La desea cerrar inmediatamente?';
                this.closeSolicitud(question);

              }
              // If Solicitud isn't completed yet
              else
              {
                // Notify success update and go back to list
                NotificationsService.showToast(
                  response.message,
                  NotificationsService.messageType.success
                );
  
                this.goTo_solicitudesList();
              }
              
              break;
            }
  
            case 1: { // Export partes list to Excel
  
              this.loading = false;
              this.loadSolicitud(1);
  
              break;
            }
  
            default: {
  
              break;
            }
          }
          
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
                  'Error al intentar guardar la solicitud',
                  NotificationsService.messageType.error
                );

                break;
              }
          }

          this.loading = false;
        }
      );
  }

  public closeSolicitud(question: string | null): void{
    Swal.fire({
      title: 'Cerrar solicitud',
      text: question !== null ? question : `¿Realmente quieres cerrar la solicitud #${ this.solicitud.id }?`,
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
          title: 'Cerrando..',
          icon: 'warning',
          showConfirmButton: false,
          showCancelButton: false,
          allowOutsideClick: false,
          showLoaderOnConfirm: true,
          preConfirm: () => {

          }    
        }]);

        this._solicitudesService.closeSolicitud(this.solicitud.id)
        .subscribe(
          //Success request
          (response: any) => {

            NotificationsService.showToast(
              response.message,
              NotificationsService.messageType.success
            );

            this.goTo_solicitudesList();
          },
          //Error request
          (errorResponse: any) => {

            switch(errorResponse.status)
            {
              case 400: //Object not found
              {
                NotificationsService.showAlert(
                  errorResponse.error.message,
                  NotificationsService.messageType.warning
                );

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
                  'Error al intentar cerrar la solicitud',
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

  public submitFormParte(): void {

    this.updateParte();
    this.parteForm.reset();
    this.DISPLAYING_FORM = 0;
  }

  private validateExcelRowAsParte(row: any): any {
    
    /*
    * RESPONSE CODES
    *
    *   -2: Skip error
    *   -1: Error
    *    0: Warning
    *    1: Success
    */
    let response = {
      code: 1,
      message: ''
    };

    if(row.length > 0)
    {
      try
      {
        let parte = {
          'cantidad': row[0] !== undefined ? row[0] : null,
          'nparte': row[1] !== undefined ? row[1] : null,
          'descripcion': row[2] !== undefined ? row[2] : null,
          'costo': row[3] !== undefined ? row[3] : null,
          'margen': row[4] !== undefined ? row[4] : null,
          'tiempoentrega': row[5] !== undefined ? row[5] : null,
          'peso': row[6] !== undefined ? row[6] : null,
          'backorder': row[7] !== undefined ? row[7] : null,
        };

        if(parte.nparte !== null)
        {
          let index = this.partes.findIndex((p) => {
            if(p.nparte.toUpperCase() === parte.nparte.toUpperCase())
            {
              return true;
            }
            else
            {
              return false;
            }
          });
  
          if((index < 0))
          {
            response.code = -2;
            response.message = `La parte N°:${ parte.nparte } no existe en la solicitud`;
          }
          
          if((parte.cantidad === null) || (isNaN(parte.cantidad)) || (parte.cantidad <= 0))
          {
            response.code = 0;
            response.message = `La parte N°:${ parte.nparte } tiene cantidad invalida`;
          }
          
          if(parte.costo !== null)
          {
            if((isNaN(parte.costo)) || (parte.costo < 0))
            {
              response.code = 0;
              response.message = `La parte N°:${ parte.nparte } tiene costo invalido`;
            }
          }
          
          if(parte.margen !== null)
          {
            if(parte.margen < 0)
            {
              response.code = 0;
              response.message = `La parte N°:${ parte.nparte } tiene margen invalido`;
            }
          }
          
          if(parte.tiempoentrega !== null)
          {
            if((isNaN(parte.tiempoentrega)) || (parte.tiempoentrega < 0))
            {
              response.code = 0;
              response.message = `La parte N°:${ parte.nparte } tiene tiempo de entrega invalido`;
            }
          }
          
          if(parte.peso !== null)
          {
            if((isNaN(parte.peso)) || (parte.peso < 0))
            {
              response.code = 0;
              response.message = `La parte N°:${ parte.nparte } tiene peso invalido`;
            }
          }
          
          if(parte.backorder !== null)
          {
            if((isNaN(parte.backorder)) || (parte.backorder < 0) || (parte.backorder > 1))
            {
              response.code = 0;
              response.message = `La parte N°:${ parte.nparte } tiene backorder invalido`;
            }
          }
        }
        else
        {
          response.code = -2;
          response.message = 'El archivo contiene partes sin N° de parte';
        }
        
      }
      catch(error)
      {
        response.code = -1;
        response.message = 'El archivo es invalido';
      }
    }
    else
    {
      response.code = -2;
      response.message = 'El archivo tiene una fila vacia';
    }

    return response;
  }

  public updateParte(): void {
    
    this.partes[this.parte_index].cantidad = this.parteForm.value.cantidad;
    this.partes[this.parte_index].costo = this.parteForm.value.costo;
    this.partes[this.parte_index].margen = this.parteForm.value.margen;
    this.partes[this.parte_index].tiempoentrega = this.parteForm.value.tiempoentrega;
    this.partes[this.parte_index].peso = this.parteForm.value.peso;
    this.partes[this.parte_index].flete = this.calculateParteFlete(this.partes[this.parte_index]);
    this.partes[this.parte_index].monto = this.calculateParteMonto(this.partes[this.parte_index]);
    this.partes[this.parte_index].backorder = this.parteForm.value.backorder;
    this.partes[this.parte_index].descripcion = this.parteForm.value.descripcion;
    this.partes[this.parte_index].complete = this.isParteCompleted(this.parte_index);

    this.dataUpdated = true;

    this.renderDataTable(this.datatableElement_partes);
  }

  private isParteCompleted(index: number): boolean
  {
    if((index < 0) || (index >= this.partes.length))
    {
      return false;
    }
    else
    {
      if(
          (this.partes[index].costo !== null) &&
          (this.partes[index].margen !== null) &&
          (this.partes[index].tiempoentrega !== null) &&
          (this.partes[index].peso !== null) &&
          (this.partes[index].flete !== null)
      )
      {
        return true;
      }
      else
      {
        return false;
      }
    }
  }

  public exportPartesToExcel(): void {

    if(this.dataUpdated === true)
    {
      Swal.fire({
        title: 'Actualizar partes',
        text: "La lista de partes ha sido modificada y necesita guardar los cambios antes de exportar. ¿Desea continuar?",
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
          this.completeSolicitud(1);
        }
      });
    }
    else
    {
      let data: any[] = [];
      //Push header
      data.push(
        [
          'Cantidad',
          'N parte',
          'Descripcion',
          'Costo (USD)',
          'Margen (%)',
          'Tiempo entrega (dias)',
          'Peso (lb)',
          'Backorder (SI = 1, NO = 0)'
        ]
      );

      //Add rows
      this.partes.forEach((p: any) => {
        data.push([
          p.cantidad,
          p.nparte,
          p.descripcion,
          p.costo,
          p.margen,
          p.tiempoentrega,
          p.peso,
          (p.backorder === true) ? '1' : '0',
        ]);
      });

      this._utilsService.exportTableToExcel(data, `Solicitud_${ this.solicitud.id }-Partes`);
    }
  }

  public calculateParteFlete(parte: any): number | null {
    if(
        (this.lbInUsd >= 0) &&
        (parte.peso !== null)
    )
    {
      return this.lbInUsd * parte.peso;
    }
    else
    {
      return null;
    }
  }

  public calculateParteMonto(parte: any): number | null {
    if(
        (parte.cantidad !== null) &&
        (parte.costo !== null) &&
        (parte.margen !== null) &&
        (parte.flete !== null)
    )
    {
      return parte.costo + ((parte.costo / 100) * parte.margen) + (parte.flete);
    }
    else
    {
      return null;
    }
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public goTo_updateParte(index: number): void {
    this.parte_index = index;

    this.parteForm.controls.cantidad.setValue(this.partes[this.parte_index].cantidad);
    this.parteForm.controls.costo.setValue(this.partes[this.parte_index].costo);
    this.parteForm.controls.margen.setValue(this.partes[this.parte_index].margen);
    this.parteForm.controls.tiempoentrega.setValue(this.partes[this.parte_index].tiempoentrega);
    this.parteForm.controls.peso.setValue(this.partes[this.parte_index].peso);
    this.parteForm.controls.flete.setValue(this.partes[this.parte_index].flete);
    this.parteForm.controls.descripcion.setValue(this.partes[this.parte_index].descripcion);
    this.parteForm.controls.backorder.setValue(this.partes[this.parte_index].backorder);

    this.DISPLAYING_FORM = 1;
  }

  public goTo_completeSolicitud(): void {
    this.parteForm.reset();
    this.DISPLAYING_FORM = 0;
  }

  public goTo_solicitudesList(): void {
    this.router.navigate(['/panel/solicitudes']);
  }

}
