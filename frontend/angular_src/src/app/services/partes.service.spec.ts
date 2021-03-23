import { TestBed } from '@angular/core/testing';

import { PartesService } from './partes.service';

describe('PartesService', () => {
  let service: PartesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(PartesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
