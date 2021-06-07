import { TestBed } from '@angular/core/testing';

import { EntregasService } from './entregas.service';

describe('EntregasService', () => {
  let service: EntregasService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(EntregasService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
