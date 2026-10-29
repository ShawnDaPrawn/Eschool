//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diary_item_layer.dart';
import 'package:eschool_api/src/model/diary_homework.dart';
import 'package:eschool_api/src/model/diary_subject.dart';
import 'package:eschool_api/src/model/diary_item_source.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diary_school_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiarySchoolItem {
  /// Returns a new [DiarySchoolItem] instance.
  DiarySchoolItem({
    required this.source_,

    required this.id,

    required this.title,

    required this.date,

    required this.dueAt,

    required this.endDate,

    required this.subject,

    required this.homework,

    required this.state,
  });

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiaryItemSource.unknownDefaultOpenApi,
  )
  final DiaryItemSource source_;

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  /// The day it shows on (today for overdue homework).
  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final DateTime date;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'due_at', required: true, includeIfNull: true)
  final String? dueAt;

  @JsonKey(name: r'end_date', required: true, includeIfNull: true)
  final DateTime? endDate;

  @JsonKey(name: r'subject', required: true, includeIfNull: true)
  final DiarySubject? subject;

  @JsonKey(name: r'homework', required: true, includeIfNull: true)
  final DiaryHomework? homework;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final DiaryItemLayer state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiarySchoolItem &&
          other.source_ == source_ &&
          other.id == id &&
          other.title == title &&
          other.date == date &&
          other.dueAt == dueAt &&
          other.endDate == endDate &&
          other.subject == subject &&
          other.homework == homework &&
          other.state == state;

  @override
  int get hashCode =>
      source_.hashCode +
      id.hashCode +
      title.hashCode +
      date.hashCode +
      (dueAt == null ? 0 : dueAt.hashCode) +
      (endDate == null ? 0 : endDate.hashCode) +
      (subject == null ? 0 : subject.hashCode) +
      (homework == null ? 0 : homework.hashCode) +
      state.hashCode;

  factory DiarySchoolItem.fromJson(Map<String, dynamic> json) =>
      _$DiarySchoolItemFromJson(json);

  Map<String, dynamic> toJson() => _$DiarySchoolItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
