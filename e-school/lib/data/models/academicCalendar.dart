//this is a local data model to manage items in the academic calendar
import 'package:flutter/material.dart';

class AcademicCalendar<T> {
  AcademicCalendar({
    required this.data,
    required this.date,
    required this.highlightColor,
    this.rangeEndDate,
  });
  T data;
  DateTime? date;
  DateTime? rangeEndDate;
  Color highlightColor;
}
