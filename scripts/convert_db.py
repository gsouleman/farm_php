import re
import os

input_file = r'C:\FarmBackup_stable_v1.0\database_dump.sql'
output_file = r'c:\Farm2.0_PHP\sql\data_import.sql'

def transform_val(val):
    if val == r'\N':
        return 'NULL'
    if val == 't':
        return '1'
    if val == 'f':
        return '0'
    
    # Handle HexEWKB (PostGIS) -> MySQL WKB
    # PostGIS EWKB (Little Endian): 01 + [Type(4 bytes with flags)] + [SRID(4 bytes)] + [Coords]
    # Standard WKB (Little Endian): 01 + [Type(4 bytes)] + [Coords]
    if re.match(r'^[0-9A-F]+$', val) and len(val) > 40:
        endian = val[0:2]
        type_flags = val[2:10]
        if endian == '01' and type_flags.endswith('20'):
            # It's HexEWKB with SRID flag 0x20000000
            wkb_type = type_flags[0:6] + '00' # Strip SRID flag (32-bit int: 03 00 00 20 -> 03 00 00 00)
            coordinates = val[18:] # Skip SRID (8 hex chars)
            clean_wkb = endian + wkb_type + coordinates
            return f"ST_GeomFromWKB(UNHEX('{clean_wkb}'), 4326)"
        elif endian == '01' and type_flags in ['01000000', '02000000', '03000000']:
            # Simple WKB Point/Line/Polygon
            return f"ST_GeomFromWKB(UNHEX('{val}'), 4326)"

    # Handle timestamps with timezone (+00)
    if re.match(r'^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}', val):
        val = val.split('+')[0]
    
    # Escape single quotes
    val = val.replace("'", "''")
    return f"'{val}'"

try:
    with open(input_file, 'r', encoding='utf-8') as f, open(output_file, 'w', encoding='utf-8') as out:
        out.write("SET NAMES utf8mb4;\n")
        out.write("SET FOREIGN_KEY_CHECKS = 0;\n")
        out.write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n")
        
        current_table = None
        columns = None
        in_copy = False
        row_count = 0
        
        for line in f:
            line = line.strip('\n')
            
            # Match COPY public.table (cols) FROM stdin;
            match = re.match(r'^COPY public\.(\w+) \((.*)\) FROM stdin;', line)
            if match:
                current_table = match.group(1)
                if current_table == 'spatial_ref_sys':
                    in_copy = False
                    continue
                
                # Normalize column names: "createdAt" -> `created_at` in our schema if they differ, 
                # but let's keep it simple and just use backticks.
                cols_list = [c.strip().strip('"') for c in match.group(2).split(',')]
                # Manual map of PG column names to MySQL schema if needed
                # For this project, PG "createdAt" -> MySQL `created_at` (usually)
                # But let's check schema.sql. It uses `created_at` lowercase.
                normalized_cols = []
                for c in cols_list:
                    if c == 'createdAt': normalized_cols.append('created_at')
                    elif c == 'updatedAt': normalized_cols.append('updated_at')
                    else: normalized_cols.append(c)
                
                columns = ", ".join([f"`{c}`" for c in normalized_cols])
                in_copy = True
                print(f"Processing table: {current_table}")
                continue
                
            if in_copy:
                if line == r'\.':
                    in_copy = False
                    out.write("\n")
                    continue
                
                vals = line.split('\t')
                transformed_vals = [transform_val(v) for v in vals]
                out.write(f"REPLACE INTO `{current_table}` ({columns}) VALUES ({', '.join(transformed_vals)});\n")
                row_count += 1

        out.write("\nSET FOREIGN_KEY_CHECKS = 1;\n")
    print(f"Successfully converted {row_count} rows to {output_file}")
except Exception as e:
    print(f"Error: {e}")
