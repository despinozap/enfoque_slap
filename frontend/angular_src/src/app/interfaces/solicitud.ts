export interface Solicitud {
  id: number,
  cliente_id: number,
  user_id: number,
  estadosolicitud_id: number,
  comentario: string,
  partes: any[]
}