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
      let sValue = value.toFixed(2);

      let index = sValue.length - 3; 

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

      return response + sValue.substring(sValue.length - 3, sValue.length);
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
