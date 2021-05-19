import { Injectable } from '@angular/core';
import { WorkBook, WorkSheet } from 'xlsx/types';

/* XLSX lib */
import * as XLSX from 'xlsx';

/* JsPDF */
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

@Injectable({
  providedIn: 'root'
})
export class UtilsService {

  constructor() { }

  public exportHtmlToPdf(htmlContent: HTMLElement, filename: string): void {
    const doc = new jsPDF('p', 'pt', 'a4');
    const options = {
      background: 'white',
      scale: 3
    };

    if(htmlContent !== null)
    {
      html2canvas(htmlContent, options)
        .then((canvas) => {
          const img = canvas.toDataURL('image/PNG');
          // Añadir imagen Canvas a PDF
          const bufferX = 15;
          const bufferY = 15;
          const imgProps = (doc as any).getImageProperties(img);
          const pdfWidth = doc.internal.pageSize.getWidth() - 2 * bufferX;
          const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
          doc.addImage(img, 'PNG', bufferX, bufferY, pdfWidth, pdfHeight, undefined, 'FAST');

          return doc;
        })
        .then((docResult) => {
          docResult.save(filename);
        });
    }
    
  }

  public exportTableToExcel(data: any[], title: string): void {
    //Create a new book
    const wb: WorkBook = XLSX.utils.book_new();
    //Convert the table to a sheet
    const ws: WorkSheet = XLSX.utils.aoa_to_sheet(data);
    
    //Add the sheet to the book
    XLSX.utils.book_append_sheet(wb, ws, title);

    let today = new Date();
    let filename = `${today.getFullYear()}${this.parseNumberToTwoDigits(today.getMonth())}${this.parseNumberToTwoDigits(today.getDay())}-${this.parseNumberToTwoDigits(today.getHours())}${this.parseNumberToTwoDigits(today.getMinutes())}${this.parseNumberToTwoDigits(today.getSeconds())}_${title}.xlsx`; 
    //Save file
    XLSX.writeFile(wb, filename);
  }

  public validateInputFile(target: DataTransfer, exts: string[]): string
  {
    //Filter for multiple files
    if(target.files.length === 1)
    {
      //Filter for invalid extension
      let lastDot = target.files[0].name.lastIndexOf('.');
      if((lastDot >= 0) && (lastDot < target.files[0].name.length - 1))
      {
        let ext = target.files[0].name.substring(lastDot + 1, target.files[0].name.length).toLowerCase();

        if(exts.includes(ext))
        {
          // Valid filetype, no validation message
          return '';
        }
        else
        {
          return 'Tipo de archivo no permitido';
        }
      }
      else
      {
        return 'Tipo de archivo invalido';
      }
    }
    else if(target.files.length < 1)
    {
      return 'No se ha cargado correctamente el archivo';
    }
    else(target.files.length > 1)
    {
      return 'No puedes cargar multiples archivos';
    }
  }

  public generateMenu(role_id: number): any {

    //Base menu
    let menu = [
      {
        'title': 'Menu',
        'groups': [
          {
            'title': 'Dashboard',
            'icon': 'bx-home-circle',
            'header': false,
            'route': 'panel'
          }
        ]
      }
    ];

    switch(role_id)
    {

      case 1: { // Administrador

        let roleMenu = {
          'title': 'Modulos',
          'groups': [
            {
              'title': 'Sistema',
              'icon': 'bx-cog',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Parametros de sistema',
                  'route': 'parameters'
                }
              ]
            },
            {
              'title': 'Usuarios',
              'icon': 'bxs-user-detail',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nuevo usuario',
                  'route': 'usuarios/create'
                },
                {
                  'title': 'Lista de usuarios',
                  'route': 'usuarios'
                }
              ]
            },
            {
              'title': 'Compradores',
              'icon': 'bx-purchase-tag-alt',
              'header': false,
              'route': '/panel/compradores'
            },
            {
              'title': 'Clientes',
              'icon': 'bxs-briefcase-alt',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nuevo cliente',
                  'route': 'clientes/create'
                },
                {
                  'title': 'Lista de clientes',
                  'route': 'clientes'
                }
              ]
            },
            {
              'title': 'Partes',
              'icon': 'bx-barcode',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Lista de partes',
                  'route': 'partes'
                }
              ]
            },
            {
              'title': 'Solicitudes',
              'icon': 'bx-list-ol',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nueva solicitud',
                  'route': 'solicitudes/create'
                },
                {
                  'title': 'Lista de solicitudes',
                  'route': 'solicitudes'
                }
              ]
            },
            {
              'title': 'Cotizaciones',
              'icon': 'bx-money',
              'header': false,
              'route': '/panel/cotizaciones'
            },
            {
              'title': 'OCs',
              'icon': 'bx-hive',
              'header': false,
              'route': '/panel/ocs'
            },
            {
              'title': 'Recepciones',
              'icon': 'bx-log-in-circle',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nueva recepcion',
                  'route': '/panel/recepciones/comprador/create'
                },
                {
                  'title': 'Lista de recepciones',
                  'route': '/panel/recepciones/comprador'
                }
              ]
            },
            {
              'title': 'Despachos',
              'icon': 'bx-log-out-circle',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nuevo despacho',
                  'route': '/panel/despachos/comprador/create'
                },
                {
                  'title': 'Lista de despachos',
                  'route': '/panel/despachos/comprador'
                }
              ]
            },
          ]
        };

        menu.push(roleMenu);

        break;
      }

      case 2: { // Vendedor

        let roleMenu = {
          'title': 'Modulos',
          'groups': [
            {
              'title': 'Solicitudes',
              'icon': 'bx-list-ol',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Nueva solicitud',
                  'route': 'solicitudes/create'
                },
                {
                  'title': 'Lista de solicitudes',
                  'route': 'solicitudes'
                }
              ]
            },
            {
              'title': 'Cotizaciones',
              'icon': 'bx-money',
              'header': true,
              'route': '',
              'items': [
                {
                  'title': 'Lista de cotizaciones',
                  'route': 'cotizaciones'
                }
              ]
            },
            {
              'title': 'OCs',
              'icon': 'bx-hive',
              'header': false,
              'route': '/panel/ocs'
            }
          ]
        };

        menu.push(roleMenu);

        break;
      }

      default: {

        break;
      }

    }

    return menu;
  }

  public dateStringFormat(value: string): string {
    
    let strDate = null;
    if(value != null)
    {
      try
      {
        let dt = null;
        if(value.length >= 10)
        {
          dt = new Date(value);
        }
        else
        {
          dt = new Date(`${value} 00:00:00`);
        }

        strDate = `${dt.getFullYear()}-${ (dt.getMonth() + 1) < 10 ? '0' + (dt.getMonth() + 1) : (dt.getMonth() + 1)}-${ dt.getDate() < 10 ? '0' + dt.getDate() : dt.getDate()}`
      }
      catch(ex)
      {
        strDate = '';
      }
    }
    else
    {
      strDate = '';
    }

    return strDate;
  }

  public moneyStringFormat(value: number): string {
    if(!isNaN(value))
    {
      let sValue = value.toString();
      let dotIndex = sValue.indexOf('.');

      let index = (dotIndex >= 0) ? dotIndex : sValue.length; 

      let response = '';
      let counter = 0;
      while(index > 0)
      {
        response = sValue[--index] + response;

        if((++counter === 3) && (index > 0))
        {
          response = ',' + response;
          counter = 0;
        }
      }

      return (dotIndex > 0) ? response + sValue.substring(dotIndex, sValue.length) : response;
    }
    else
    {
      return '';
    }
    
  }

  public parseNumberToTwoDigits(n: number): string {
    if(!isNaN(n))
    {
      let strN = n.toString();
    
      return strN.length < 2 ? `0${strN}` : strN;
    }
    else
    {
      return '';
    }
  }
}
