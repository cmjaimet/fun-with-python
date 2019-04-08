# could use Django for templating but not for this exercise

def display_character( char, abilities, skills ):
    table1 = make_table( abilities, False )
    table2 = make_table( skills, False )
    html = "<p><b>Class:</b> {}</p>\n" \
        "<p><b>Race:</b> {}, {}</p>\n" \
        "{}\n" \
        "{}" \
        .format(
            char["class"],
            char["race"],
            char["subrace"],
            table1,
            table2
        )
    return html

def make_table( data, display ):
    table = "<table>\n"
    table += make_row( data["fields"], True )
    for value in data["values"]:
        table += make_row( value, False )
    table += "</table>\n"
    if display:
        print( table )
        return
    return table

def make_row( data, header ):
    css = ' style="font-weight:bold;"' if header else ""
    row = "<tr{}>\n".format( css )
    for elem in data:
        row += "\t<td>{}</td>\n".format( elem )
    row += "</tr>\n"
    return row
