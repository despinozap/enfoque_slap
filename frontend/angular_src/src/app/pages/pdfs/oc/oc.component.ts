import { Component, OnInit } from '@angular/core';
import { NotificationsService } from 'src/app/services/notifications.service';
import { UtilsService } from 'src/app/services/utils.service';

@Component({
  selector: 'app-pdf-oc',
  templateUrl: './oc.component.html',
  styleUrls: ['./oc.component.css']
})
export class PDFOcComponent implements OnInit {

  reportData: any = {
    oc: {
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
      delivery_name: null,
      delivery_address: null,
      delivery_city: null,
      delivery_email: null,
      delivery_phone: null
    },
    partes: []
  };

  constructor(
    private _utilsService: UtilsService
  ) { }

  ngOnInit(): void {
  }

  public loadReportData(ocData: any)
  { 
    if(ocData.partes.length > 0)
    {
      this.reportData.oc.currentdate = ocData.oc.currentdate;
      this.reportData.oc.id = ocData.oc.id;
      this.reportData.oc.created_at = ocData.oc.created_at;
      this.reportData.oc.comprador_name = ocData.oc.cotizacion.solicitud.comprador.name;
      this.reportData.oc.comprador_address = ocData.oc.cotizacion.solicitud.comprador.address;
      this.reportData.oc.comprador_city = ocData.oc.cotizacion.solicitud.comprador.city;
      this.reportData.oc.comprador_email = ocData.oc.cotizacion.solicitud.comprador.email;
      this.reportData.oc.comprador_phone = ocData.oc.cotizacion.solicitud.comprador.phone;
      this.reportData.oc.supplier_name = ocData.oc.proveedor.name;
      this.reportData.oc.supplier_address = ocData.oc.proveedor.address;
      this.reportData.oc.supplier_city = ocData.oc.proveedor.city;
      this.reportData.oc.supplier_email = ocData.oc.proveedor.email;
      this.reportData.oc.supplier_phone = ocData.oc.proveedor.phone;
      // If OC's proveedor is delivered
      if(ocData.oc.proveedor.delivered === 1)
      {
        this.reportData.oc.delivery_name = ocData.proveedor.delivery_name;
        this.reportData.oc.delivery_address = ocData.proveedor.delivery_address;
        this.reportData.oc.delivery_city = ocData.proveedor.delivery_city;
        this.reportData.oc.delivery_email = ocData.proveedor.delivery_email;
        this.reportData.oc.delivery_phone = ocData.proveedor.delivery_phone;
      }
      else
      {
        this.reportData.oc.delivery_name = null;
        this.reportData.oc.delivery_address = null;
        this.reportData.oc.delivery_city = null;
        this.reportData.oc.delivery_email = null;
        this.reportData.oc.delivery_phone = null;
      }
      
      this.reportData.partes = ocData.partes;
    }
    else
    {
      NotificationsService.showToast(
        'Error al intentar cargar la lista de partes',
        NotificationsService.messageType.error
      );
    }
  }

  public exportOcToPdf(): void {
    const data = document.getElementById('divReport');
    if(data !== null)
    {
      this._utilsService.exportHtmlToPdf(data, `OC-${ this.reportData.oc.id }.pdf`);
    }    
  }

  public getTotalNetoPartes(): number
  {
    return this.reportData.partes.reduce(
      (carry: any, parte: any) => {
        return carry + (parte.costo * parte.cantidad);
      },
      0
    );
  }

  public moneyStringFormat(value: number): string {
    return this._utilsService.moneyStringFormat(value);
  }

  public dateStringFormat(value: string): string {
    return this._utilsService.dateStringFormat(value);
  }

}
