DROP INDEX IF EXISTS idx_network_gist_address;
CREATE INDEX IF NOT EXISTS idx_network_gist_address ON network.networks USING GIST (address inet_ops);

DROP INDEX IF EXISTS idx_network_btree_address;
CREATE INDEX IF NOT EXISTS idx_network_btree_address ON network.networks (address);

DROP INDEX IF EXISTS idx_network_btree_masklen;
CREATE INDEX IF NOT EXISTS idx_network_btree_masklen ON network.networks (masklen(address));