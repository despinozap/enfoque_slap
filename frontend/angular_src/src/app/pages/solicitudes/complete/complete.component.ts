import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
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

  solicitud: any = {
    id: null,
    cliente_name: null,
    marca_name: null,
    estadosolicitud_id: -1,
    estadosolicitud_name: null,
    comentario: null
  };

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
    peso: new FormControl('', [Validators.min(1)]),
    flete: new FormControl('', [Validators.min(0)]),
    backorder: new FormControl(''),
    descripcion: new FormControl(''),
  });

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private _solicitudesService: SolicitudesService,
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
    this.sub = this.route.params.subscribe(params => {
      this.solicitud.id = params['id'];
      this.loadSolicitud(0);
    });
  }

  ngOnDestroy() {
    this.sub.unsubscribe();
  }

  private loadFormData(solicitudData: any)
  {
    if(solicitudData['partes'].length > 0)
    {
      if((solicitudData.estadosolicitud.id === 1) || (solicitudData.estadosolicitud.id === 2)) // If is 'Pendiente' or 'Completada'
      {
        this.solicitud.id = solicitudData.id;
        this.solicitud.cliente_name = solicitudData.cliente.name;
        this.solicitud.marca_name = solicitudData.partes[0].marca.name;
        this.solicitud.estadosolicitud_id = solicitudData.estadosolicitud.id,
        this.solicitud.estadosolicitud_name = solicitudData.estadosolicitud.name;
        this.solicitud.comentario = solicitudData.comentario;

        this.partes = [];
        solicitudData.partes.forEach((p: any) => {
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
              'backorder': p.pivot.backorder,
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
          `No puedes completar una solicitud con estado ${ solicitudData.estadosolicitud.name }`,
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
            if(response.code === 1)
            {
              index = this.partes.findIndex((p) => {
                if(p.nparte.toUpperCase() === sheet[i][0].toUpperCase())
                {
                  return true;
                }
                else
                {
                  return false;
                }
              });

              parte = this.partes[index];
              parte.cantidad = sheet[i][1] !== undefined ? sheet[i][1] : null;
              parte.costo = sheet[i][2] !== undefined ? sheet[i][2] : null;
              parte.margen = sheet[i][3] !== undefined ? sheet[i][3] : null;
              parte.tiempoentrega = sheet[i][4] !== undefined ? sheet[i][4] : null;
              parte.peso = sheet[i][5] !== undefined ? sheet[i][5] : null;
              parte.flete = sheet[i][6] !== undefined ? sheet[i][6] : null;
              parte.monto = null;
              parte.descripcion = sheet[i][7] !== undefined ? sheet[i][7] : null;
              parte.backorder = (sheet[i][8] !== undefined) && (sheet[i][8] === '1') ? true : false;

              if((parte.costo !== null) && (parte.margen !== null) && (parte.flete !== null))
              {
                parte.monto = (parte.costo * parte.cantidad) + ((parte.costo * parte.cantidad) * parte.margen / 100) + parte.flete;
              }

              parte.complete = this.isParteCompleted(index);
              this.dataUpdated= true;

            }
            else if(response.code === 0)
            {
              NotificationsService.showToast(
                response.message,
                NotificationsService.messageType.error
              );
            }
          }
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
    
    this.loading = true;

    this._solicitudesService.getSolicitud(this.solicitud.id)
    .subscribe(
      //Success request
      (response: any) => {
        
        switch(afterExecutedAction){
          
          case 0: { // Nothing

            this.loading = false;
            this.loadFormData(response.data);
            
            break;
          }

          case 1: { // Export partes list to Excel

            this.loading = false;
            this.loadFormData(response.data);

            this.dataUpdated = false;

            this.exportPartesToExcel();

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

          switch(afterExecutedAction)
          {
          
            case 0: { // Go back to the list

              NotificationsService.showToast(
                response.message,
                NotificationsService.messageType.success
              );

              this.loadFormData(response.data);

              if(this.solicitud.estadosolicitud_id === 2)
              {
                this.loading = false;

                Swal.fire({
                  title: 'Solicitud completada',
                  text: `La solicitud #${ this.solicitud.id } se ha completado. ¿Deseas cerrarla?`,
                  icon: 'question',
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
                      title: 'Cerrando solicitud..',
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
                  else
                  {
                    this.goTo_solicitudesList();
                  }
                });
              }
              else
              {
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

            case 422: //Invalid request parameters
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

  public closeSolicitud(): void{
    Swal.fire({
      title: 'Cerrar solicitud',
      text: `¿Realmente quieres cerrar la solicitud #${ this.solicitud.id }?`,
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
    
    let response = {
      code: 1,
      message: ''
    };

    try
    {
      let parte = {
        'nparte': row[0]  !== undefined ? row[0] : null,
        'cantidad': row[1] !== undefined ? row[1] : null,
        'costo': row[2] !== undefined ? row[2] : null,
        'margen': row[3] !== undefined ? row[3] : null,
        'tiempoentrega': row[4] !== undefined ? row[4] : null,
        'peso': row[5] !== undefined ? row[5] : null,
        'flete': row[6] !== undefined ? row[6] : null,
        'descripcion': row[7] !== undefined ? row[7] : null,
        'backorder': row[8] !== undefined ? row[8] : null,
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
          response.code = -1;
          response.message = 'Uno de los N° de parte en la lista no existe en la solicitud';
        }
        
        if((parte.cantidad === null) || (isNaN(parte.cantidad)) || (parte.cantidad <= 0))
        {
          response.code = 0;
          response.message = 'La parte N°: ' + parte.nparte + ' tiene cantidad invalida';
        }
        
        if(parte.costo !== null)
        {
          if((isNaN(parte.costo)) || (parte.costo < 0))
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene costo invalido';
          }
        }
        
        if(parte.margen !== null)
        {
          if(parte.margen < 0)
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene margen invalido';
          }
        }
        
        if(parte.tiempoentrega !== null)
        {
          if((isNaN(parte.tiempoentrega)) || (parte.tiempoentrega < 0))
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene tiempo de entrega invalido';
          }
        }
        
        if(parte.peso !== null)
        {
          if((isNaN(parte.peso)) || (parte.peso < 0))
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene peso invalido';
          }
        }
        
        if(parte.flete !== null)
        {
          if((isNaN(parte.flete)) || (parte.flete < 0))
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene valor de flete invalido';
          }
        }
        
        if(parte.backorder !== null)
        {
          if((isNaN(parte.backorder)) || (parte.backorder < 0) || (parte.backorder > 1))
          {
            response.code = 0;
            response.message = 'La parte N°: ' + parte.nparte + ' tiene backorder invalido';
          }
        }

      }
      else
      {
        response.code = -1;
        response.message = 'Todas las partes de la lista debe contener el N° de parte';
      }

      return response;
    }
    catch(error)
    {
      console.log(error);
      response.code = -1;
      response.message = 'EL archivo es invalido';

      return response;
    }
  }

  public updateParte(): void {

    this.partes[this.parte_index].cantidad = this.parteForm.value.cantidad;
    this.partes[this.parte_index].costo = this.parteForm.value.costo;
    this.partes[this.parte_index].margen = this.parteForm.value.margen;
    this.partes[this.parte_index].tiempoentrega = this.parteForm.value.tiempoentrega;
    this.partes[this.parte_index].peso = this.parteForm.value.peso;
    this.partes[this.parte_index].flete = this.parteForm.value.flete;
    if((this.partes[this.parte_index].costo !== null) && (this.partes[this.parte_index].margen !== null) && (this.partes[this.parte_index].flete !== null))
    {
      this.partes[this.parte_index].monto = (this.partes[this.parte_index].costo * this.partes[this.parte_index].cantidad) + ((this.partes[this.parte_index].costo * this.partes[this.parte_index].cantidad) * this.partes[this.parte_index].margen / 100) + this.partes[this.parte_index].flete;
    }
    else
    {
      this.partes[this.parte_index].monto = null;
    }
    this.partes[this.parte_index].backorder = this.parteForm.value.backorder;
    this.partes[this.parte_index].descripcion = this.parteForm.value.descripcion;
    this.partes[this.parte_index].complete = this.isParteCompleted(this.parte_index);

    this.dataUpdated = true;
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
          (this.partes[index].flete !== null) &&
          (this.partes[index].monto !== null)
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
          'N parte',
          'Cantidad',
          'Costo (USD)',
          'Margen (%)',
          'Tiempo entrega (dias)',
          'Peso (kg)',
          'Valor flete (USD)',
          'Descripcion',
          'Backorder (SI = 1, NO = 0)'
        ]
      );

      //Add rows
      this.partes.forEach((p: any) => {
        data.push([
          p.nparte,
          p.cantidad,
          p.costo,
          p.margen,
          p.tiempoentrega,
          p.peso,
          p.flete,
          p.descripcion,
          (p.backorder === true) ? '1' : '0',
        ]);
      });

      this._utilsService.exportTableToExcel(data, `Solicitud_${ this.solicitud.id }-Partes`);
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
