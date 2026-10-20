import 'package:dio/dio.dart';
import 'package:eschool/data/models/chatMessage.dart';
import 'package:eschool/data/models/chatUser.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/constants.dart';

class ChatRepository {
  Future<Map<String, dynamic>> fetchChatUsers({
    required int offset,
    required bool isParent,
    String? searchString,
  }) async {
    try {
      final response = await Api.get(
        url: isParent ? Api.getChatUsersParent : Api.getChatUsersStudent,
        useAuthToken: true,
        queryParameters: {
          'offset': offset,
          'limit': offsetLimitPaginationAPIDefaultItemFetchLimit,
          if (searchString != null) 'search': searchString,
        },
      );

      final List<ChatUser> chatUsers = [];

      for (int i = 0; i < response['data']['items'].length; i++) {
        chatUsers.add(ChatUser.fromJsonAPI(response['data']['items'][i]));
      }

      return {
        'chatUsers': chatUsers,
        'totalItems': response['data']['total_items'] ?? 1,
        'totalUnreadUsers': response['data']['total_unread_users'] ?? 0,
      };
    } catch (error) {
      throw ApiException(error.toString());
    }
  }

  Future<Map<String, dynamic>> fetchChatMessages({
    required int offset,
    required String chatUserId,
    required bool isParent,
  }) async {
    try {
      final response = await Api.post(
        url: isParent ? Api.getChatMessagesParent : Api.getChatMessagesStudent,
        useAuthToken: true,
        body: {
          'offset': offset,
          'user_id': chatUserId,
          'limit': offsetLimitPaginationAPIDefaultItemFetchLimit,
        },
      );

      final List<ChatMessage> chatMessage = [];

      for (int i = 0; i < response['data']['items'].length; i++) {
        chatMessage.add(ChatMessage.fromJsonAPI(response['data']['items'][i]));
      }

      return {
        'chatMessages': chatMessage,
        'totalItems': response['data']['total_items'],
      };
    } catch (error) {
      throw ApiException(error.toString());
    }
  }

  Future<ChatMessage> sendChatMessage({
    required String message,
    required int receiverId, required bool isParent, List<String> filePaths = const [],
  }) async {
    try {
      final List<MultipartFile> files = [];
      for (final filePath in filePaths) {
        files.add(await MultipartFile.fromFile(filePath));
      }
      final result = await Api.post(
        body: {
          'receiver_id': receiverId.toString(),
          'message': message,
          if (files.isNotEmpty) 'file': files,
        },
        url: isParent ? Api.sendChatMessageParent : Api.sendChatMessageStudent,
        useAuthToken: true,
      );
      return ChatMessage.fromJsonAPI(result['data']);
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> readAllMessages({
    required String userId,
    required bool isParent,
  }) async {
    try {
      //this will call API to make all messages read, noting in failure
      await Api.post(
        url: isParent ? Api.readAllMessagesParent : Api.readAllMessagesStudent,
        useAuthToken: true,
        body: {
          'user_id': userId,
        },
      );
    } catch (_) {}
  }
}
