import 'package:eschool_teacher/data/models/chatUser.dart';
import 'package:eschool_teacher/data/repositories/chatRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/*
This cubit will handle teacher's chat users (parents and students)
*/

abstract class ChatUsersSearchState {}

class ChatUsersSearchInitial extends ChatUsersSearchState {}

class ChatUsersSearchFetchInProgress extends ChatUsersSearchState {}

class ChatUsersSearchFetchFailure extends ChatUsersSearchState {

  ChatUsersSearchFetchFailure({required this.errorMessage});
  final String errorMessage;
}

class ChatUsersSearchFetchSuccess extends ChatUsersSearchState {

  ChatUsersSearchFetchSuccess({
    required this.chatUsers,
    required this.totalOffset,
    required this.moreChatUserFetchError,
    required this.moreChatUserFetchProgress,
  });
  final List<ChatUser> chatUsers;
  final int totalOffset;
  final bool moreChatUserFetchError;
  final bool moreChatUserFetchProgress;

  ChatUsersSearchFetchSuccess copyWith({
    List<ChatUser>? newChatUsers,
    int? newTotalOffset,
    bool? newFetchMorechatUsersInProgress,
    bool? newFetchMorechatUsersError,
  }) {
    return ChatUsersSearchFetchSuccess(
      chatUsers: newChatUsers ?? chatUsers,
      totalOffset: newTotalOffset ?? totalOffset,
      moreChatUserFetchProgress:
          newFetchMorechatUsersInProgress ?? moreChatUserFetchProgress,
      moreChatUserFetchError:
          newFetchMorechatUsersError ?? moreChatUserFetchError,
    );
  }
}

abstract class ChatUsersSearchCubit extends Cubit<ChatUsersSearchState> {

  ChatUsersSearchCubit(this._chatRepository) : super(ChatUsersSearchInitial());
  final ChatRepository _chatRepository;

  Future<void> fetchChatUsers({required String searchString}) async {
    emit(ChatUsersSearchFetchInProgress());
    try {
      final Map<String, dynamic> data = await _chatRepository.fetchChatUsers(
        offset: 0,
        searchString: searchString,
        isParent: this is ParentChatUserSearchCubit,
      );
      return emit(
        ChatUsersSearchFetchSuccess(
          chatUsers: data['chatUsers'],
          totalOffset: data['totalItems'],
          moreChatUserFetchError: false,
          moreChatUserFetchProgress: false,
        ),
      );
    } catch (e) {
      emit(
        ChatUsersSearchFetchFailure(
          errorMessage: e.toString(),
        ),
      );
    }
  }

  Future<void> fetchMoreChatUsers({required String searchString}) async {
    if (state is ChatUsersSearchFetchSuccess) {
      final stateAs = state as ChatUsersSearchFetchSuccess;
      if (stateAs.moreChatUserFetchProgress) {
        return;
      }
      try {
        emit(stateAs.copyWith(newFetchMorechatUsersInProgress: true));

        final Map moreTransactionResult = await _chatRepository.fetchChatUsers(
          offset: stateAs.chatUsers.length,
          isParent: this is ParentChatUserSearchCubit,
          searchString: searchString,
        );

        final List<ChatUser> chatUsers = stateAs.chatUsers;

        chatUsers.addAll(moreTransactionResult['chatUsers']);

        emit(
          ChatUsersSearchFetchSuccess(
            chatUsers: chatUsers,
            totalOffset: moreTransactionResult['totalItems'],
            moreChatUserFetchError: false,
            moreChatUserFetchProgress: false,
          ),
        );
      } catch (e) {
        emit(
          (state as ChatUsersSearchFetchSuccess).copyWith(
            newFetchMorechatUsersInProgress: false,
            newFetchMorechatUsersError: true,
          ),
        );
      }
    }
  }

  bool hasMore() {
    if (state is ChatUsersSearchFetchSuccess) {
      return (state as ChatUsersSearchFetchSuccess).chatUsers.length <
          (state as ChatUsersSearchFetchSuccess).totalOffset;
    }
    return false;
  }

  void emitInit() {
    emit(ChatUsersSearchInitial());
  }
}

//two cubits to handle two different user's data but with same base functionality
class ParentChatUserSearchCubit extends ChatUsersSearchCubit {
  ParentChatUserSearchCubit(super.chatRepository);
}

class StudentChatUsersSearchCubit extends ChatUsersSearchCubit {
  StudentChatUsersSearchCubit(super.chatRepository);
}
