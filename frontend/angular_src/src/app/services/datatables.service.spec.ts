import { TestBed } from '@angular/core/testing';

import { DatatablesService } from './datatables.service';

describe('DatatablesService', () => {
  let service: DatatablesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DatatablesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
