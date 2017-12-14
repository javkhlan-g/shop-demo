-------------------------------------------------------
-- Export file for user STU_LOAN                     --
-- Created by altansukh.a on 12/13/2017, 12:38:00 PM --
-------------------------------------------------------

create table PRODUCTS
(
  PRODUCT_ID NUMBER(11) not null,
  NAME       VARCHAR2(100),
  SKU        VARCHAR2(14),
  PRICE      NUMBER(15,2) not null,
  IMAGE      VARCHAR2(50) not null
)
tablespace USERS
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );
alter table PRODUCTS
  add primary key (PRODUCT_ID)
  using index 
  tablespace USERS
  pctfree 10
  initrans 2
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );

  
create table SHOP_TRANSACTION
(
  TRANSACTION_ID NUMBER(10) not null,
  TRACE_NUMBER   NUMBER(10)
)
tablespace USERS
  pctfree 10
  initrans 1
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );
alter table SHOP_TRANSACTION
  add primary key (TRANSACTION_ID)
  using index 
  tablespace USERS
  pctfree 10
  initrans 2
  maxtrans 255
  storage
  (
    initial 64K
    next 1M
    minextents 1
    maxextents unlimited
  );


create sequence SHOP_TRANSACTION_SEQ
minvalue 1
maxvalue 9999999999999999999999999999
start with 181
increment by 1
cache 20;


create or replace trigger shop_transaction_trigger
before insert on shop_transaction
for each row
   
begin
     select shop_transaction_seq.nextval into :new.transaction_id from dual;
   end;
/


spool off



insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (2, 'iPhone X', 'IPHO001', 1400.00, 'images/iphone.jpg');

insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (3, 'Camera', 'CAME001', 700.00, 'images/camera.jpg');

insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (6, 'Watch', 'WATC001', 100.00, 'images/watch.jpg');

insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (1, 'Macbook Pro', 'MAC001', 250000.00, 'images/macbook.jpg');

insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (5, 'HeadPhones', 'HP0002', 1000.00, 'images/headphones.png');

insert into products (PRODUCT_ID, NAME, SKU, PRICE, IMAGE)
values (4, 'Samsung S8', 'S8005', 35000.00, 'images/samsung.png');
