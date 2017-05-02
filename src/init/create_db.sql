/*==============================================================*/
/* table: sessions                                              */
/*==============================================================*/

set names utf8;
-- --基础服务---/*{{{*/
drop table if exists sessions;
create table sessions
(
    sesskey        varchar(32) not null,
    expiry         int(11),
    value          text,
    primary key(sesskey)
) type = innodb;

drop table if exists id_genter;
create table id_genter
(
    id             int(11) not null,
    obj            varchar(30),
    step           int(11)
) type = innodb;

insert into id_genter(id, obj, step) values(1, 'other', 10);
--/*}}}*/ 


