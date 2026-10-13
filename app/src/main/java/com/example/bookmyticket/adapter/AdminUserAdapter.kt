package com.example.bookmyticket.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.example.bookmyticket.R
import com.example.bookmyticket.databinding.ItemAdminUserBinding
import com.example.bookmyticket.models.User
import com.example.bookmyticket.utils.ImageUtils

class AdminUserAdapter(
    private var users: List<User>,
    private val onEditRole: (User) -> Unit
) : RecyclerView.Adapter<AdminUserAdapter.ViewHolder>() {

    class ViewHolder(val binding: ItemAdminUserBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemAdminUserBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val user = users[position]
        holder.binding.tvName.text = user.fullName
        holder.binding.tvEmail.text = user.email
        holder.binding.tvRole.text = user.role.uppercase(java.util.Locale.getDefault())
        
        ImageUtils.loadImage(
            holder.binding.ivUser, 
            user.profileImage, 
            com.example.bookmyticket.R.drawable.ic_profile
        )

        holder.binding.btnEditRole.setOnClickListener { onEditRole(user) }
    }

    override fun getItemCount() = users.size

    fun updateData(newUsers: List<User>) {
        users = newUsers
        notifyDataSetChanged()
    }
}