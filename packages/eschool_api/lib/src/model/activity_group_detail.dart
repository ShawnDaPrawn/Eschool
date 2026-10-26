//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_group.dart';
import 'package:eschool_api/src/model/activity_session.dart';
import 'package:eschool_api/src/model/activity_material_view.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_group_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityGroupDetail {
  /// Returns a new [ActivityGroupDetail] instance.
  ActivityGroupDetail({
    required this.group,

    required this.materials,

    required this.sessions,

    required this.canManage,

    required this.canCapture,

    this.chatAvailable,
  });

  @JsonKey(name: r'group', required: true, includeIfNull: false)
  final ActivityGroup group;

  @JsonKey(name: r'materials', required: true, includeIfNull: false)
  final List<ActivityMaterialView> materials;

  @JsonKey(name: r'sessions', required: true, includeIfNull: false)
  final List<ActivitySession> sessions;

  @JsonKey(name: r'can_manage', required: true, includeIfNull: false)
  final bool canManage;

  @JsonKey(name: r'can_capture', required: true, includeIfNull: false)
  final bool canCapture;

  /// Only present when chat_persona was supplied; fresh current persona/group/module/policy access, independent of group management permission.
  @JsonKey(name: r'chat_available', required: false, includeIfNull: false)
  final bool? chatAvailable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityGroupDetail &&
          other.group == group &&
          other.materials == materials &&
          other.sessions == sessions &&
          other.canManage == canManage &&
          other.canCapture == canCapture &&
          other.chatAvailable == chatAvailable;

  @override
  int get hashCode =>
      group.hashCode +
      materials.hashCode +
      sessions.hashCode +
      canManage.hashCode +
      canCapture.hashCode +
      chatAvailable.hashCode;

  factory ActivityGroupDetail.fromJson(Map<String, dynamic> json) =>
      _$ActivityGroupDetailFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityGroupDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
