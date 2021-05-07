import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class RecepcionesService {

  constructor(private httpClient: HttpClient) { }

  public getQueuePartes_comprador(comprador_id: number, proveedor_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/proveedores/${proveedor_id}/queuepartes`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepciones_comprador(comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones`;
    
    return this.httpClient.get(endpoint);
  }
}
