import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class RecepcionesService {

  constructor(private httpClient: HttpClient) { }

  /*
   *  Sucursal
   */
  public removeRecepcion_sucursal(sucursal_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'DELETE',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };
    
    return this.httpClient.delete(endpoint, httpOptions);
  }

  public updateRecepcion_sucursal(sucursal_id: number, recepcion_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, recepcion, httpOptions);
  }

  public prepareUpdateRecepcion_sucursal(sucursal_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones/${recepcion_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepcion_sucursal(sucursal_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones/${recepcion_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeRecepcion_sucursal(sucursal_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, recepcion, httpOptions);
  }

  public getQueuePartes_sucursal(sucursal_id: number, centrodistribucion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/centrosdistribucion/${centrodistribucion_id}/queuepartes`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepciones_sucursal(sucursal_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/sucursales/${sucursal_id}/recepciones`;
    
    return this.httpClient.get(endpoint);
  }

  /*
   *  Sucursal (centro)
   */
  public removeRecepcion_centrodistribucion(centrodistribucion_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'DELETE',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };
    
    return this.httpClient.delete(endpoint, httpOptions);
  }

  public updateRecepcion_centrodistribucion(centrodistribucion_id: number, recepcion_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, recepcion, httpOptions);
  }

  public prepareUpdateRecepcion_centrodistribucion(centrodistribucion_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones/${recepcion_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepcion_centrodistribucion(centrodistribucion_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones/${recepcion_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeRecepcion_centrodistribucion(centrodistribucion_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, recepcion, httpOptions);
  }

  public getQueuePartes_centrodistribucion(centrodistribucion_id: number, comprador_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/compradores/${comprador_id}/queuepartes`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepciones_centrodistribucion(centrodistribucion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/centrosdistribucion/${centrodistribucion_id}/recepciones`;
    
    return this.httpClient.get(endpoint);
  }

  /*
   *  Comprador
   */
  public removeRecepcion_comprador(comprador_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'DELETE',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };
    
    return this.httpClient.delete(endpoint, httpOptions);
  }

  public updateRecepcion_comprador(comprador_id: number, recepcion_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}`;

    let httpOptions = { 
      method: 'PUT',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET,POST,OPTIONS,DELETE,PUT'
      }
    };
    
    return this.httpClient.put(endpoint, recepcion, httpOptions);
  }

  public prepareUpdateRecepcion_comprador(comprador_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}/prepare`;
    
    return this.httpClient.get(endpoint);
  }

  public getRecepcion_comprador(comprador_id: number, recepcion_id: number): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones/${recepcion_id}`;
    
    return this.httpClient.get(endpoint);
  }

  public storeRecepcion_comprador(comprador_id: number, recepcion: any): Observable<any>
  {
    let endpoint: string = `${environment.ENDPOINT_BASE}/compradores/${comprador_id}/recepciones`;

    let httpOptions = { 
      method: 'POST',
      headers:
      {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    return this.httpClient.post(endpoint, recepcion, httpOptions);
  }

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
