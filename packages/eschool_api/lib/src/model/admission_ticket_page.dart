//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/admission_ticket.dart';
import 'package:json_annotation/json_annotation.dart';

part 'admission_ticket_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTicketPage {
  /// Returns a new [AdmissionTicketPage] instance.
  AdmissionTicketPage({
    required this.schoolId,

    required this.actorUserId,

    required this.items,

    required this.page,

    required this.lastPage,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<AdmissionTicket> items;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTicketPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory AdmissionTicketPage.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTicketPageFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTicketPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
