#!/usr/bin/env bash
##
# Transfer data from local mysql to rds
# Usage ./localmysql_transfer_to_rds.sh
#


SOURCE_HOST=
SOURCE_PORT=
SOURCE_USER=
SOURCE_PASSWD=

RDS_HOST=
RDS_USER=
RDS_PASSWD=

DBNAME=
CHARSET=utf8

TMP_TRANSFER_DIR=/data/rds_transfer

MYSQL=/usr/bin/mysql
MYSQLDUMP=/usr/bin/mysqldump


dump_data() {
    dump_data_result_file=${TMP_TRANSFER_DIR}/${DBNAME}.data.dumpresult
    $MYSQLDUMP -h ${SOURCE_HOST} -u ${SOURCE_USER} -P ${SOURCE_PORT} -p${SOURCE_PASSWD} \
        --opt --default-character=${CHARSET} --hex-blob ${DBNAME} --skip-triggers \
        --force --result-file $dump_data_result_file  > ${TMP_TRANSFER_DIR}/${DBNAME}.sql
    dump_success=`tail -1 ${dump_data_result_file} | grep "Dump completed on" | wc -l`
    if [ $dump_success -eq 0 ]; then
        echo "dump data failed, more infomation please see result file: ${dump_data_result_file}"
        exit 1
    fi
}

# dump stored procedure, trigger and function
dump_others() {
    $MYSQLDUMP -h ${SOURCE_HOST} -u ${SOURCE_USER} -P ${SOURCE_PORT} -p${SOURCE_PASSWD} \
        --opt --default-character=${CHARSET} --hex-blob ${DBNAME} -R \
        | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' \
        > ${TMP_TRANSFER_DIR}/${DBNAME}.triggerProcedure.sql
}


load_data_to_rds() {
    $MYSQL -h ${RDS_HOST} -u ${RDS_USER} -p${RDS_PASSWD} ${DBNAME} < ${TMP_TRANSFER_DIR}/${DBNAME}.sql
}


load_others_to_rds() {
    $MYSQL -h ${RDS_HOST} -u ${RDS_USER} -p${RDS_PASSWD} ${DBNAME} < ${TMP_TRANSFER_DIR}/${DBNAME}.triggerProcedure.sql
}

dump_data && dump_others && load_data_to_rds && load_others_to_rds
