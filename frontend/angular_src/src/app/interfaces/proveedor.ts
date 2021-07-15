export interface Proveedor {
  id: number,
  rut: string,
  name: string,
  address: string,
  city: string,
  email: string,
  phone: string,
  delivered: boolean,
  delivery_name: string,
  delivery_address: string,
  delivery_city: string,
  delivery_email: string,
  delivery_phone: string,
  comprador: any
}
