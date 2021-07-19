import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OcComponent } from './oc.component';

describe('OcComponent', () => {
  let component: OcComponent;
  let fixture: ComponentFixture<OcComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OcComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(OcComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
