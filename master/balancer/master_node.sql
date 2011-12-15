CREATE TABLE workers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instance_name VARCHAR(50),
    address VARCHAR(100),
    load INT,
    last_task_timestamp INT(11)
);


CREATE TABLE data_sets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annotation_set,
    type,
    status
);


CREATE TABLE genes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data_set_id FOREIGN KEY,
    index,
    gene_id
);